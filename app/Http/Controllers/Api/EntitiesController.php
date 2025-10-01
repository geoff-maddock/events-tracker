<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Filters\EntityFilters;
use App\Http\Requests\EntityRequest;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\EntityResource;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Action;
use App\Models\Activity;
use App\Models\Alias;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Follow;
use App\Models\Photo;
use App\Models\Link;
use App\Models\Location;
use App\Models\Contact;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\EventPublished;
use App\Services\Embeds\OembedExtractor;
use App\Services\ImageHandler;
use App\Services\SessionStore\ListParameterSessionStore;
use App\Services\StringHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Jamband\Ripple\Ripple;
use Storage;

class EntitiesController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

    protected bool $hasFilter;

    protected EntityFilters $filter;

    public function __construct(EntityFilters $filter)
    {
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.entities.';

        // default list variables
        $this->defaultLimit = 5;
        $this->defaultSort = 'created_at';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['entities.created_at' => 'desc'];

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;

        $this->hasFilter = false;

        $this->middleware('auth:sanctum')->only([
            'attendJson',
            'unattendJson',
            'store',
            'update',
            'destroy',
            'followJson',
            'unfollowJson',
            'addLocation',
            'addLink',
            'addContact',
            'updateLocation',
            'updateLink',
            'updateContact',
            'deleteLink',
            'deleteLocation',
            'deleteContact',
        ]);

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @throws \Throwable
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Entity::query()
                        ->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')
                        ->leftJoin('entity_statuses', 'entities.entity_status_id', '=', 'entity_statuses.id')
                        ->select('entities.*')
                        ->withCount('follows')
        ;

        // set the default filter to active
        $defaultFilter = ['entity_status' => 'Active'];

        // TODO Change the query param requirements for filter and sort to use a simpler or more robust format

        // set the filter and base query
        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultFilters($defaultFilter)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the entities
        $entities = $query->paginate($listResultSet->getLimit());

        return response()->json(new EntityCollection($entities));
    }


    /**
     * Display a listing of the most popular entities.
     */
    public function popular(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $limit = (int) $request->get('limit', 30);
        $from = Carbon::now()->subDays($days);

        $query = Entity::query()
            ->withCount([
                'events as events_count' => function ($q) use ($from) {
                    $q->where('start_at', '>=', $from);
                },
                'follows as follows_count' => function ($q) use ($from) {
                    $q->where('created_at', '>=', $from);
                },
            ])
            ->filter($this->filter);

        $entities = $query
            ->orderBy(DB::raw('events_count + follows_count'), 'desc')
            ->paginate($limit);

        $entities->getCollection()->transform(function ($entity) {
            $entity->popularity_score = $entity->events_count + $entity->follows_count;

            return $entity;
        });

        return response()->json(new EntityCollection($entities));
    }


    /**
     * Display a listing of the resource that the user is following.
     *
     * @throws \Throwable
     */
    public function indexFollowing(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        $this->middleware('auth');

        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        //$baseQuery = Entity::query()->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')->select('entities.*');

        $baseQuery = Entity::join('follows', 'entities.id', '=', 'follows.object_id')
        ->where('follows.object_type', '=', 'entity')
        ->where('follows.user_id', '=', $this->user->id)
        ->select('entities.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort($this->defaultSortCriteria);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the entities
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('entities.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('entities'))
            ->with(['type' => 'Following'])
            ->render();
    }

    protected function getDefaultFilters(): array
    {
        return [];
    }

    /**
     * Gets the base query.
     */
    public function getBaseQuery(): Builder
    {
        return Entity::query()->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')->select('entities.*');
    }

    /**
     * Display a listing of entities by type.
     *
     * @throws \Throwable
     */
    public function indexTypes(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $type
    ): string {
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_roles');
        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = $this->getBaseQuery();

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entities.name' => 'asc'])
            ->setParentFilter(['entity_type' => $type]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the threads
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('entities.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(['type' => $type])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Display a listing of entities by role.
     *
     * @throws \Throwable
     */
    public function indexRoles(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        ?string $role
    ): string {
        // initialized listParamSessionStore with baseindex key
        // list entity result builder
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_roles');
        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = $this->getBaseQuery();

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entities.name' => 'asc'])
            ->setParentFilter(['role' => $role]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the threads
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('entities.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(['role' => $role])
            ->with(compact('entities'))
            ->render();
    }



    /**
     * Reset the rpp, sort, order.
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set the rpp, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_entity_index';
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('api.entities.index');
    }

    /**
     * Reset the filtering of entities.
     *
     * @throws \Throwable
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): Response {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_entity_index';
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route('api.entities.index');
    }

    /**
     * Display a listing of entities by tag.
     *
     * @throws \Throwable
     */
    public function indexTags(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $slug,
        StringHelper $stringHelper
    ): string {
        // get the tag by the slug name
        $tag = Tag::where('slug', '=', $slug)->firstOrFail();

        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_tags');
        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = $this->getBaseQuery();

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entities.name' => 'asc'])
            ->setParentFilter(['tag' => $slug]);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the threads
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('entities.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(['tag' => $tag])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Display a listing of entities by alias.
     *
     * @throws \Throwable
     */
    public function indexAliases(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder,
        string $alias
    ): string {
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_tags');
        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Entity::getByAlias($alias)->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')->select('entities.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entities.name' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the threads
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('entities.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(['role' => $alias])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Display an entity when passed the slug.
     *
     * @return Response|string
     *
     * @throws \Throwable
     */
    public function indexSlug(string $slug)
    {
        $entity = Entity::getBySlug(strtolower($slug))->firstOrFail();

        return view('entities.show')
            ->with(compact('entity'))
            ->render();
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(EntityRequest $request, Entity $entity): JsonResponse
    {
        $msg = '';

        $input = $request->all();

        $input['slug'] = Str::slug($request->input('slug', '-'));

        // Set the user fields explicitly
        $input['created_by'] = $this->user->id;
        $input['updated_by'] = $this->user->id;

        $tagArray = $request->input('tag_list', []);
        $aliasArray = $request->input('alias_list', []);
        $syncArray = [];
        $aliasSyncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->slug = Str::slug($tag);
                $newTag->tag_type_id = 1;
                $newTag->save();

                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        // check the elements in the alias list, and if any don't match, add the alias
        foreach ($aliasArray as $key => $alias) {
            if (DB::table('aliases')->where('id', $alias)->count() > 0) {
                $newAlias = new Alias();
                $newAlias->name = ucwords(strtolower($alias));
                $newAlias->save();

                $aliasSyncArray[] = $newAlias->id;

                $msg .= ' Added alias '.$alias.'.';
            } else {
                $aliasSyncArray[$key] = $alias;
            }
        }

        $entity = $entity->create($input);

        $entity->tags()->attach($syncArray);
        $entity->aliases()->attach($aliasSyncArray);
        $entity->roles()->attach($request->input('role_list', []));

        // add to activity log
        Activity::log($entity, $this->user, 1);

        // return response()->json($entity);
        return response()->json(new EntityResource($entity));
    }

    /**
     * Display the specified resource.
     */
    public function show(Entity $entity): JsonResponse
    {
        app('redirect')->setIntendedUrl(url()->current());

        return response()->json(new EntityResource($entity));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Entity $entity, EntityRequest $request): JsonResponse
    {
        $msg = '';

        $input = $request->all();

        $input['slug'] = Str::slug($request->input('slug', '-'));

        $entity->fill($input)->save();

        // if we got this far, it worked
        $msg = 'Updated entity. ';

        $tagArray = $request->input('tag_list', []);
        $aliasArray = $request->input('alias_list', []);

        $syncArray = [];
        $aliasSyncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->slug = Str::slug($tag);
                $newTag->tag_type_id = 1;
                $newTag->save();

                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[strtolower($tag)] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        // check the elements in the alias list, and if any don't match, add the alias
        foreach ($aliasArray as $key => $alias) {
            if (!Alias::find($alias)) {
                $newAlias = new Alias();
                $newAlias->name = ucwords(strtolower($alias));
                $newAlias->save();

                $aliasSyncArray[strtolower($alias)] = $newAlias->id;

                $msg .= ' Added alias '.$alias.'.';
            } else {
                $aliasSyncArray[$key] = $alias;
            }
        }

        $entity->tags()->sync($syncArray);
        $entity->aliases()->attach($aliasSyncArray);
        $entity->roles()->sync($request->input('role_list', []));

        // add to activity log
        Activity::log($entity, $this->user, 2);

        return response()->json(new EntityResource($entity));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Entity $entity): JsonResponse
    {
        // add to activity log
        Activity::log($entity, $this->user, 3);

        $entity->delete();

        return response()->json([], 204);
    }

    /**
     * Add a photo to an entity.
     */
    public function addPhoto(int $id, Request $request, ImageHandler $imageHandler): JsonResponse
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif,webp',
        ]);

        $fileName = time().'_'.$request->file->getClientOriginalName();
        $filePath = $request->file('file')->storePubliclyAs('photos', $fileName, 'external');

        // attach to entity
        if ($entity = Entity::find($id)) {
            $photo = $imageHandler->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if (isset($entity->photos) && 0 === count($entity->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            // attach to entity
            $entity->addPhoto($photo);

            $photoData = $photo->getApiResponse();

            return response()->json($photoData, 201);
        }

        return response()->json([], 404);
    }

    /**
     * Add a link to an entity.
     */
    public function addLink(int $id, Request $request): JsonResponse
    {
        $this->validate($request, [
            'text' => ['required', 'min:3'],
            'url' => ['required', 'min:3'],
        ]);

        if ($entity = Entity::find($id)) {
            $input = $request->only(['text', 'url', 'title', 'is_primary']);
            $input['is_primary'] = isset($input['is_primary']) ? 1 : 0;
            $link = Link::create($input);
            $entity->links()->attach($link->id);

            // add to activity log
            Activity::log($link, $this->user, Action::CREATE);

            return response()->json($link, 201);
        }

        return response()->json([], 404);
    }

    /**
     * Update a link on an entity.
     */
    public function updateLink(int $id, int $linkId, Request $request): JsonResponse
    {
        $this->validate($request, [
            'text' => ['sometimes', 'required', 'min:3'],
            'url' => ['sometimes', 'required', 'min:3'],
            'title' => ['nullable', 'string'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        if ($entity = Entity::find($id)) {
            $link = $entity->links()->find($linkId);
            if ($link) {
                if ($request->user()->id !== ($link->created_by ?? $entity->created_by)) {
                    return response()->json([], 403);
                }

                $input = $request->only(['text', 'url', 'title', 'is_primary']);
                if (array_key_exists('is_primary', $input)) {
                    $input['is_primary'] = $input['is_primary'] ? 1 : 0;
                }
                $link->update($input);

                // add to activity log
                Activity::log($link, $this->user, Action::UPDATE);

                return response()->json($link);
            }
        }

        return response()->json([], 404);
    }

    /**
     * Add a location to an entity.
     */
    public function addLocation(int $id, Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => ['required', 'min:3'],
            'slug' => ['required', 'min:3', 'regex:/^[a-z0-9-]+$/'],
            'city' => ['required', 'min:3'],
            'visibility_id' => ['required'],
            'location_type_id' => ['required'],
        ]);

        if ($entity = Entity::find($id)) {
            $input = $request->all();
            $input['entity_id'] = $id;
            $location = new Location($input);
            $location->created_by = $request->user()->id;
            $location->save();

            // add a location to activity log
            Activity::log($location, $this->user, Action::CREATE);

            return response()->json($location, 201);
        }

        return response()->json([], 404);
    }

    /**
     * Update a location on an entity.
     */
    public function updateLocation(int $id, int $locationId, Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => ['sometimes', 'required', 'min:3'],
            'slug' => ['sometimes', 'required', 'min:3', 'regex:/^[a-z0-9-]+$/'],
            'city' => ['sometimes', 'required', 'min:3'],
            'visibility_id' => ['sometimes', 'required'],
            'location_type_id' => ['sometimes', 'required'],
        ]);

        if ($entity = Entity::find($id)) {
            $location = $entity->locations()->find($locationId);
            if ($location) {
                if ($request->user()->id !== ($location->created_by ?? $entity->created_by)) {
                    return response()->json([], 403);
                }

                $input = $request->all();
                $location->update($input);

                // add to activity log
                Activity::log($location, $this->user, Action::UPDATE);

                return response()->json($location);
            }
        }

        return response()->json([], 404);
    }

    /**
     * Add a contact to an entity.
     */
    public function addContact(int $id, Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => ['required', 'min:3'],
            'type' => ['required', 'min:3'],
            'visibility_id' => ['required', 'integer'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'other' => ['nullable', 'string'],
        ]);

        if ($entity = Entity::find($id)) {
            $input = $request->only(['name', 'email', 'phone', 'other', 'type', 'visibility_id']);
            $contact = Contact::create($input);
            $entity->contacts()->attach($contact->id);

            Activity::log($contact, $this->user, Action::CREATE);

            return response()->json($contact, 201);
        }

        return response()->json([], 404);
    }

    /**
     * Update a contact on an entity.
     */
    public function updateContact(int $id, int $contactId, Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => ['sometimes', 'required', 'min:3'],
            'type' => ['sometimes', 'required', 'min:3'],
            'visibility_id' => ['sometimes', 'required', 'integer'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'other' => ['nullable', 'string'],
        ]);

        if ($entity = Entity::find($id)) {
            $contact = $entity->contacts()->find($contactId);
            if ($contact) {
                if ($request->user()->id !== ($contact->created_by ?? $entity->created_by)) {
                    return response()->json([], 403);
                }

                $input = $request->only(['name', 'email', 'phone', 'other', 'type', 'visibility_id']);
                $contact->update($input);

                Activity::log($contact, $this->user, Action::UPDATE);
                return response()->json($contact);
            }
        }

        return response()->json([], 404);
    }

    /**
     * Delete a link from an entity.
     */
    public function deleteLink(int $id, int $linkId, Request $request): JsonResponse
    {
        if ($entity = Entity::find($id)) {
            $link = $entity->links()->find($linkId);
            if ($link) {
                if ($request->user()->id !== ($link->created_by ?? $entity->created_by)) {
                    return response()->json([], 403);
                }

                // add to activity log
                Activity::log($link, $this->user, Action::DELETE);

                $entity->links()->detach($linkId);
                $link->delete();

                return response()->json([], 204);
            }
        }

        return response()->json([], 404);
    }

    /**
     * Delete a location from an entity.
     */
    public function deleteLocation(int $id, int $locationId, Request $request): JsonResponse
    {
        if ($entity = Entity::find($id)) {
            $location = $entity->locations()->find($locationId);
            if ($location) {
                if ($request->user()->id !== ($location->created_by ?? $entity->created_by)) {
                    return response()->json([], 403);
                }

                // add to activity log
                Activity::log($location, $this->user, Action::DELETE);

                $location->delete();

                return response()->json([], 204);
            }
        }

        return response()->json([], 404);
    }

    /**
     * Delete a contact from an entity.
     */
    public function deleteContact(int $id, int $contactId, Request $request): JsonResponse
    {
        if ($entity = Entity::find($id)) {
            $contact = $entity->contacts()->find($contactId);
            if ($contact) {
                if ($request->user()->id !== ($contact->created_by ?? $entity->created_by)) {
                    return response()->json([], 403);
                }

                // add to activity log
                Activity::log($contact, $this->user, Action::DELETE);

                $entity->contacts()->detach($contactId);
                $contact->delete();

                return response()->json([], 204);
            }
        }

        return response()->json([], 404);
    }

    protected function makePhoto(UploadedFile $file): Photo
    {
        return Photo::named($file->getClientOriginalName())->makeThumbnail();
    }

    /**
     * Mark user as following the entity.
     *
     * @return Response|array
     *
     * @throws \Throwable
     */
    public function follow(int $id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        // check if the entity exists
        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // check if the user already follows
        if ($entity->followedBy($this->user) !== null) {
            flash()->error('Error', 'You are already following '.$entity->name);

            return redirect()->route('entities.show', compact('entity'));
        }

        // add the following response
        $follow = new Follow();
        $follow->object_id = $id;
        $follow->user_id = $this->user->id;
        $follow->object_type = 'entity'; // 1 = Attending, 2 = Interested, 3 = Uninterested, 4 = Cannot Attend
        $follow->save();

        Log::info('User '.$id.' is following '.$entity->name);

        // add to activity log
        Activity::log($entity, $this->user, 6);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are now following the entity - '.$entity->name,
                'Success' => view('entities.single')
                    ->with(compact('entity'))
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are now following the entity - '.$entity->name);

        return redirect()->intended('/entities/'.$entity->slug);
    }

    /**
     * Mark user as unfollowing the entity.
     *
     * @return Response|array
     *
     * @throws \Throwable
     */
    public function unfollow(int $id, Request $request)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // delete the follow
        $follow = Follow::where('object_id', '=', $id)->where('user_id', '=', $this->user->id)->where('object_type', '=', 'entity')->first();
        $follow->delete();

        // add to activity log
        Activity::log($entity, $this->user, 7);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are no longer following the entity - '.$entity->name,
                'Success' => view('entities.single')
                    ->with(compact('entity'))
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are no longer following the entity - '.$entity->name);

        return back();
    }

    /**
     * Follow the entity and return JSON.
     */
    public function followJson(Entity $entity, Request $request): JsonResponse
    {
        $user = $request->user();

        $follow = Follow::where('object_type', 'entity')
            ->where('object_id', $entity->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$follow) {
            $follow = new Follow();
            $follow->object_id = $entity->id;
            $follow->user_id = $user->id;
            $follow->object_type = 'entity';
            $follow->save();

            Activity::log($entity, $user, 6);
        }

        return response()->json(new EntityResource($entity));
    }

    /**
     * Unfollow the entity and return JSON.
     */
    public function unfollowJson(Entity $entity, Request $request): JsonResponse
    {
        $user = $request->user();

        $follow = Follow::where('object_type', 'entity')
            ->where('object_id', $entity->id)
            ->where('user_id', $user->id)
            ->first();

        if ($follow) {
            $follow->delete();
            Activity::log($entity, $user, 7);
        }

        return response()->json([], 204);
    }

    /**
     * Tweet this event.
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function tweet(int $id)
    {
        // check if there is a logged in user
        if (!$this->user) {
            flash()->error('Error', 'No user is logged in.');

            return back();
        }

        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // Add a twitter notification
        $entity->notify(new EventPublished());

        Log::info('User '.$id.' tweeted '.$entity->name);

        flash()->success('Success', 'You tweeted the entity - '.$entity->name);

        return back();
    }

    /**
     * Get the default sort array.
     */
    protected function getDefaultSortCriteria(): array
    {
        return ['id' => 'desc'];
    }

    protected function unauthorized(EntityRequest $request): RedirectResponse | Response
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        \Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['entities.name' => 'Name', 'entity_types.name' => 'Entity Type', 'entities.created_at' => 'Created At', 'follows_count' => 'Popularity'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFilterOptions(): array
    {
        return [
            'tagOptions' => ['' => '&nbsp;'] + Tag::orderBy('name', 'ASC')->pluck('name', 'slug')->all(),
            'roleOptions' => ['' => ''] + Role::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'entityTypeOptions' => ['' => ''] + EntityType::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'entityStatusOptions' => ['' => ''] +  EntityStatus::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }

    protected function getFormOptions(): array
    {
        return [
            'entityTypeOptions' => ['' => ''] + EntityType::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'entityStatusOptions' => EntityStatus::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'tagOptions' => Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'aliasOptions' => Alias::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'roleOptions' => Role::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'userOptions' => ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
        ];
    }

    public function embeds(?Entity $entity,  OembedExtractor $embedExtractor): JsonResponse
    {
        if (!$entity) {
            abort(404);
        }

        // extract all the links from the entity
        $embedList = $embedExtractor->getEmbedsForEntity($entity);

        // create a paginated list of embeds, but for now just using one page
        $embeds = [
            'data' => $embedList,
            'total' => count($embedList),
            'current_page' => 1,
            'per_page' => 100,
            'first_page_url' => '/entities/'.$entity->id.'/embeds',
            'from' => 1,
            'last_page' => 1,
            'next_page_url' => '/entities/'.$entity->id.'/embeds',
            'path' => '/entities/'.$entity->id.'/embeds',
            'prev_page_url' => '/entities/'.$entity->id.'/embeds',
            'to' => count($embedList),
        ];

        
        // converts array of embeds into json embed list
        return response()->json($embeds);
    }

    public function minimalEmbeds(?Entity $entity,  OembedExtractor $embedExtractor): JsonResponse
    {
        if (!$entity) {
            abort(404);
        }

        // extract all the links from the entity body and convert into embeds
        $embedExtractor->setLayout("small");
        $embedList = $embedExtractor->getEmbedsForEntity($entity);

        // create a paginated list of embeds, but for now just using one page
        $embeds = [
            'data' => $embedList,
            'total' => count($embedList),
            'current_page' => 1,
            'per_page' => 100,
            'first_page_url' => '/entities/'.$entity->id.'/minimal-embeds',
            'from' => 1,
            'last_page' => 1,
            'next_page_url' => '/entities/'.$entity->id.'/minimal-embeds',
            'path' => '/entities/'.$entity->id.'/minimal-embeds',
            'prev_page_url' => '/entities/'.$entity->id.'/minimal-embeds',
            'to' => count($embedList),
        ];

        
        // converts array of embeds into json embed list
        return response()->json($embeds);
    }

    public function links(?Entity $entity): JsonResponse
    {
        if (!$entity) {
            abort(404);
        }

        $links = $entity->links()->get();

        return response()->json($links);
    }

    public function locations(?Entity $entity): JsonResponse
    {
        if (!$entity) {
            abort(404);
        }

        $locations = $entity->locations()->get();

        return response()->json($locations);
    }

    public function contacts(?Entity $entity): JsonResponse
    {
        if (!$entity) {
            abort(404);
        }

        $contacts = $entity->contacts()->get();

        return response()->json($contacts);
    }

    public function photos(?Entity $entity): JsonResponse
    {
        if (!$entity) {
            abort(404);
        }

        $photos = [];
    
        // extract all the links from the entity
        $photoList = $entity->photos()->get();

        foreach ($photoList as $photo) {
            $photos[] = $photo->getApiResponse();
        }

        return response()->json($photos);
    }
}
