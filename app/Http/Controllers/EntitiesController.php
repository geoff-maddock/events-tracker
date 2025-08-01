<?php

namespace App\Http\Controllers;

use App\Filters\EntityFilters;
use App\Http\Requests\EntityRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Activity;
use App\Models\Action;
use App\Models\Alias;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Follow;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\EventPublished;
use App\Services\Embeds\EmbedExtractor;
use App\Services\ImageHandler;
use App\Services\SessionStore\ListParameterSessionStore;
use App\Services\Integrations\Instagram;
use App\Services\StringHelper;
use Carbon\Carbon;
use Exception;
use Storage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

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
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update', 'follow']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.entities.';

        // default list variables
        $this->defaultLimit = 5;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['entities.created_at' => 'desc'];

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;

        $this->hasFilter = false;

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
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Entity::query()
        ->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')
        ->select('entities.*')
        ->withCount('follows')
        ;

        // set the default filter to active
        $defaultFilter = ['entity_status' => 'Active'];

        // if the user is not logged in, only show active entities
        // check if there is a logged in user
        if (!isset($this->user)) {
            $baseQuery = Entity::query()
            ->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')
            ->select('entities.*')
            ->where('entity_status_id', '<>', EntityStatus::UNLISTED)
            ->withCount('follows')
            ;
    
        } else {
            $baseQuery = Entity::query()
            ->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')
            ->select('entities.*')
            ->withCount('follows')
            ;    
        }

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
        $entities = $query
            ->with('tags', 'events','entityType','locations','entityStatus','user')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        // count the most common entities in the recent past
        $latestEntities = Entity::withCount(['events' => function (Builder $query) {
            $query->where('events.start_at', '>', Carbon::now()->subMonths(3));
        }])
        ->with('tags', 'events','entityType','locations','entityStatus','roles','user')
        ->orderBy('events_count', 'desc')
        ->paginate(6);

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
            ->with(compact('entities', 'latestEntities'))
            ->render();
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

        // get the entities
        $entities = $query->paginate($listResultSet->getLimit());

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
     * Filter the list of entities.
     *
     * @throws \Throwable
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only entities are returned
        $baseQuery = Entity::query()
                        ->leftJoin('entity_types', 'entities.entity_type_id', '=', 'entity_types.id')
                        ->leftJoin('entity_statuses', 'entities.entity_status_id', '=', 'entity_statuses.id')
                        ->select('entities.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entities.name' => 'asc']);

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

        return redirect()->route('entities.index');
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

        return redirect()->route('entities.index');
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
        // Get the tag by the slug name
        $tag = Tag::where('slug', '=', $slug)->firstOrFail();

        $listParamSessionStore->setBaseIndex('internal_entity');
        $listParamSessionStore->setKeyPrefix('internal_entity_tags');
        // Set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntitiesController::class, 'index']));

        // Create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = $this->getBaseQuery();

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entities.name' => 'asc'])
            ->setParentFilter(['tag' => $slug]);

        // Get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // Get the query builder
        $query = $listResultSet->getList();

        // Get the entities
        $entities = $query
            ->paginate($listResultSet->getLimit());

        // Save the updated session
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
     * Show the form for creating a new resource.
     *
     * @return Response|string
     */
    public function create()
    {
        return view('entities.create')->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EntityRequest $request, Entity $entity): Response
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
            if (!is_numeric($tag) || !$newTag = Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->slug = Str::slug($tag);
                $newTag->tag_type_id = 1;
                $newTag->save();

                // log adding of new tag
                Activity::log($newTag, $this->user, Action::CREATE);

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
        Activity::log($entity, $this->user, Action::CREATE);

        flash()->success('Success', 'Your entity has been created');
        return redirect()->route('entities.show', compact('entity'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Entity $entity, EmbedExtractor $embedExtractor): View
    {
        app('redirect')->setIntendedUrl(url()->current());

        // if the user is not a logged in admin, do not show unlisted entities
        if ($entity->entity_status_id == EntityStatus::UNLISTED && (!isset($this->user) || !$this->user->hasGroup('super_admin'))) {

            abort(404);
        }

        // get the threads for the entity
        $threads = $entity->threads()->paginate($this->limit);

        // pass empty embeds here - this was moved to a deferred ajax load
        $embeds = [];

        // get all the tracks as streamable URLs
        // $tracks = $embedExtractor->getTracksFromUrl('https://0h85.bandcamp.com/');
        $tracks = [];

        $futureEvents = $entity->futureEvents(5);
        $pastEvents = $entity->pastEvents(5);

        return view('entities.show', compact('entity', 'threads', 'embeds', 'tracks','futureEvents','pastEvents'));
    }

    /**
     * Load the embeds and add to the UI
     *
     * @throws \Throwable
     */
    public function loadEmbeds(int $id, EmbedExtractor $embedExtractor, Request $request): RedirectResponse | array
    {
        // load the entity
        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // extract all the links from the entity and convert into embeds
        $embeds = $embedExtractor->getEmbedsForEntity($entity);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'Added embeds to entity page.',
                'Success' => view('embeds.playlist')
                    ->with(compact('embeds'))
                    ->render(),
            ];
        }
        flash()->success('Error', 'You cannot load embeds directly');

        return back();
    }

    /**
     * Load the embeds and add to the UI
     *
     * @throws \Throwable
     */
    public function loadMinimalEmbeds(int $id, EmbedExtractor $embedExtractor, Request $request): RedirectResponse | array
    {
        // load the entity
        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // extract all the links from the event body and convert into embeds
        $embedExtractor->setLayout("small");
        $embeds = $embedExtractor->getEmbedsForEntity($entity);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'Added embeds to entity page.',
                'Success' => view('embeds.minimal-playlist')
                    ->with(compact('embeds'))
                    ->render(),
            ];
        }
        flash()->success('Error', 'You cannot load embeds directly');

        return back();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Entity $entity): View
    {
        $this->middleware('auth');

        return view('entities.edit', compact('entity'))
        ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Entity $entity, EntityRequest $request): RedirectResponse
    {
        $msg = '';

        $input = $request->all();

        $input['slug'] = Str::slug($request->input('slug', '-'));
        $input['updated_by'] = $this->user->id;

        $entity->fill($input)->save();

        // if we got this far, it worked
        $msg = 'Updated entity. ';

        $tagArray = $request->input('tag_list', []);
        $aliasArray = $request->input('alias_list', []);

        $syncArray = [];
        $aliasSyncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!is_numeric($tag) || !$newTag = Tag::find($tag)) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->slug = Str::slug($tag);
                $newTag->tag_type_id = 1;
                $newTag->save();

                // log adding of new tag
                Activity::log($newTag, $this->user, Action::CREATE);

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
        Activity::log($entity, $this->user, Action::UPDATE);

        // flash this message
        flash()->success('Success', $msg);

        return redirect()->route('entities.show', compact('entity'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Entity $entity): RedirectResponse
    {
        // add to activity log
        Activity::log($entity, $this->user, Action::DELETE);

        $entity->delete();

        return redirect('entities');
    }

    /**
     * Add a photo to an entity.
     */
    public function addPhoto(int $id, Request $request, ImageHandler $imageHandler): void
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif,webp',
        ]);

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
        }
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
        Activity::log($entity, $this->user, Action::FOLLOW);

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
        Activity::log($entity, $this->user, Action::UNFOLLOW);

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

        if (config('app.twitter_consumer_key') === '999') {
            flash()->error('Error', 'Twitter is not configured.');
        }
        
        // Add a twitter notification
        $entity->notify(new EventPublished());

        Log::info('User '.$id.' tweeted '.$entity->name);

        flash()->success('Success', 'You tweeted the entity - '.$entity->name);

        return back();
    }

    /**
     * Endpoint to post a single entity to Instagram.
     */
    public function postToInstagram(int $id, Instagram $instagram): RedirectResponse
    {
        // load the entity
        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // get the instagram account
        if (!$instagram->getIgUserId()) {
            flash()->error('Error', 'You must have an Instagram user account linked to post to Instagram.');

            return back();
        }

        // get the instagram page access token
        if (!$instagram->getPageAccessToken()) {
            flash()->error('Error', 'You must have an Instagram page linked to post to Instagram.');

            return back();
        }

        // get the image URL
        $photo = $entity->getPrimaryPhoto();

        if (!$photo) {
            flash()->error('Error', 'You must have an photo to extract the image to post to Instagram');

            return back();
        }

        $imageUrl = Storage::disk('external')->url($photo->getStoragePath());

        if (!$imageUrl) {
            flash()->error('Error', 'You must have an image url to post to Instagram');

            return back();
        }

        // get the instagram caption
        $caption = urlEncode($entity->getInstagramFormat());

        if (!$caption) {
            flash()->error('Error', 'You must have an Instagram caption linked to post to Instagram.');

            return back();
        }

        // make the instagram api calls
        // upload the image
        try {
            $igContainerId = $instagram->uploadPhoto($imageUrl, $caption);
        } catch (Exception $e) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');

            return back();
        }

        // check the container status every 5 seconds until status_code is FINISHED
        if ($instagram->checkStatus($igContainerId) === false) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');

            return back();
        }

        // pubish the image
        $result = $instagram->publishMedia($igContainerId);
        if ($result === false) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');

            return back();
        }

        // log the post to instagram
        Activity::log($entity, $this->user, 16);

        // post was successful
        flash()->success('Success', 'Successfully published to Instagram, returned id: '.$result);

        return back();
    }

   /**
     * Endpoint to post a single entity to an Instagram Story
     */
    public function postStoryToInstagram(int $id, Instagram $instagram): RedirectResponse
    {
        // die('post story to instagram');

        // load the entity
        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // get the instagram account
        if (!$instagram->getIgUserId()) {
            flash()->error('Error', 'You must have an Instagram user account linked to post to Instagram.');

            return back();
        }

        // get the instagram page access token
        if (!$instagram->getPageAccessToken()) {
            flash()->error('Error', 'You must have an Instagram page linked to post to Instagram.');

            return back();
        }

        // get the image URL
        $photo = $entity->getPrimaryPhoto();

        // die('after primary photo');

        if (!$photo) {
            flash()->error('Error', 'You must have an photo to extract the image to post to Instagram');

            return back();
        }

        $imageUrl = Storage::disk('external')->url($photo->getStoragePath());

        if (!$imageUrl) {
            flash()->error('Error', 'You must have an image url to post to Instagram');

            return back();
        }

        // get the instagram caption
        $caption = urlEncode($entity->getInstagramFormat());

        if (!$caption) {
            flash()->error('Error', 'You must have an Instagram caption linked to post to Instagram.');

            return back();
        }

        // make the instagram api calls
        // upload the image
        try {
            $igContainerId = $instagram->uploadStoryPhoto($imageUrl, $caption);
        } catch (Exception $e) {
            flash()->error('Error', 'There was an error uploading story photo to Instagram.  Please try again.');

            return back();
        }

        // check the container status every 5 seconds until status_code is FINISHED
        if ($instagram->checkStatus($igContainerId) === false) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');

            return back();
        }

        // pubish the image
        $result = $instagram->publishMedia($igContainerId);
        if ($result === false) {
            flash()->error('Error', 'There was an error posting to Instagram.  Please try again.');

            return back();
        }

        // log the post to instagram
        Activity::log($entity, $this->user, 16);

        // post was successful
        flash()->success('Success', 'Successfully published to Instagram, returned id: '.$result);

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
}
