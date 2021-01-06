<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Alias;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Follow;
use App\Http\Requests\EntityRequest;
use App\Models\Photo;
use App\Models\Role;
use App\Models\Tag;
use App\Models\TagType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\View\View;

class EntitiesController extends Controller
{
    protected string $prefix;

    protected int $defaultRpp;

    protected string $defaultSortBy;

    protected string $defaultSortOrder;

    protected int $rpp;

    protected int $page;

    protected array $sort;

    protected string $sortBy;

    protected string $sortOrder;

    protected $defaultCriteria;

    protected array $filters;

    protected bool $hasFilter;

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        // prefix for session storage
        $this->prefix = 'app.entities.';

        // default list variables
        $this->defaultRpp = 5;
        $this->defaultSortBy = 'name';
        $this->defaultSortOrder = 'asc';

        $this->rpp = 5;
        $this->sortBy = 'name';
        $this->sortOrder = 'asc';

        $this->page = 1;
        $this->sort = ['name', 'desc'];
        $this->defaultCriteria = null;
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response | string
     *
     * @throws \Throwable
     */
    public function index(Request $request)
    {
        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // get  sort, sort order, rpp from session, update from request
        $this->getPaging($filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $hasFilter = $this->hasFilter($filters);

        // base criteria
        $query = $this->buildCriteria($request);

        // get the threads
        $entities = $query->paginate($this->rpp);

        return view('entities.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter, 'filters' => $filters])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Update the page list parameters from the request.
     */
    protected function getPaging(array $filters): void
    {
        $this->sortBy = $filters['sortBy'] ?? $this->defaultSortBy;
        $this->sortOrder = $filters['sortOrder'] ?? $this->defaultSortOrder;
        if (isset($filters['rpp']) && is_numeric($filters['rpp'])) {
            $this->rpp = $filters['rpp'];
        } else {
            $this->rpp = $this->defaultRpp;
        }
    }

    /**
     * Update the page list parameters from the request.
     */
    protected function updatePaging(Request $request)
    {
        // set sort by column
        if ($request->input('sort_by')) {
            $this->sortBy = $request->input('sort_by');
        }

        // set sort direction
        if ($request->input('sort_order')) {
            $this->sortOrder = $request->input('sort_order');
        }

        // set results per page
        if ($request->input('rpp') && is_numeric($request->input('rpp'))) {
            $this->rpp = $request->input('rpp');
        }
    }

    /**
     * Get session filters.
     *
     * @return mixed
     */
    protected function getFilters(Request $request)
    {
        return $this->getAttribute($request, 'filters', $this->getDefaultFilters());
    }

    /**
     * Get user session attribute.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getAttribute(Request $request, $attribute, $default = null)
    {
        return $request->session()
            ->get($this->prefix . $attribute, $default);
    }

    protected function getDefaultFilters(): array
    {
        return [];
    }

    /**
     * Gets the base criteria.
     *
     * @return Builder
     */
    public function getBaseCriteria(Request $request): Builder
    {
        return Entity::active()
            ->orderBy('entity_type_id', 'ASC')
            ->orderBy($this->sortBy, $this->sortOrder);
    }

    /**
     * Builds the criteria from the session.
     */
    public function buildCriteria(Request $request): Builder
    {
        // get all the filters from the session
        $filters = $this->getFilters($request);

        // base criteria
        $query = Entity::active()
            ->orderBy('entity_type_id', 'ASC')
            ->orderBy($this->sortBy, $this->sortOrder);

        // add the criteria from the session
        // check request for passed filter values

        if (!empty($filters['filter_name'])) {
            // getting name from the request
            $name = $filters['filter_name'];
            $query->where('name', 'like', '%' . $name . '%');
        }

        if (!empty($filters['filter_tag'])) {
            $tag = $filters['filter_tag'];
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', '=', ucfirst($tag));
            });
        }

        if (!empty($filters['filter_role'])) {
            $role = $filters['filter_role'];
            // add has clause
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('slug', '=', strtolower($role));
            });
        }

        if (!empty($filters['filter_alias'])) {
            $alias = $filters['filter_alias'];
            $query = Entity::getByAlias(ucfirst($alias))
                ->where(function ($query) {
                    $query->active()
                        ->orWhere('created_by', '=', ($this->user ? $this->user->id : null));
                })
                ->orderBy('entity_type_id', 'ASC')
                ->orderBy('name', 'ASC');
        }

        // change this - should be seperate
        if (!empty($filters['filter_rpp'])) {
            $this->rpp = $filters['filter_rpp'];
        }

        return $query;
    }

    /**
     * Display a listing of entities by type.
     *
     * @return Response | string
     *
     * @throws \Throwable
     */
    public function indexTypes($type)
    {
        $hasFilter = 1;

        $entities = Entity::ofType(ucfirst($type))
            ->where(function ($query) {
                $query->active()
                    ->orWhere('created_by', '=', ($this->user ? $this->user->id : null));
            })
            ->orderBy('entity_type_id', 'ASC')->orderBy('name', 'ASC')
            ->paginate();

        return view('entities.index')
            ->with(['type' => $type, 'rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Display a listing of entities by role.
     *
     * @return Response | string
     *
     * @throws \Throwable
     */
    public function indexRoles(Request $request, $role)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        // modify the criteria
        $entities = Entity::getByRole(ucfirst($role))
            ->where(function ($query) {
                $query->active()
                    ->orWhere('created_by', '=', ($this->user ? $this->user->id : null));
            })
            ->orderBy('entity_type_id', 'ASC')
            ->orderBy('name', 'ASC')
            ->paginate();

        return view('entities.index')
            ->with(['role' => $role, 'rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Checks if there is a valid filter.
     */
    public function hasFilter(array $filters): bool
    {
        $arr = $filters;
        unset($arr['rpp'], $arr['sortOrder'], $arr['sortBy'], $arr['page']);

        return count(array_filter($arr, function ($x) { return !empty($x); }));
    }

    /**
     * Filter the list of entities.
     *
     * @return Response | string
     *
     * @throws \Throwable
     */
    public function filter(Request $request)
    {
        // update filters from request
        $this->setFilters($request, array_merge($this->getFilters($request), $request->all()));

        // get all the filters from the session
        $this->filters = $this->getFilters($request);

        // get  sort, sort order, rpp from session, update from request
        $this->getPaging($this->filters);
        $this->updatePaging($request);

        // set flag if there are filters
        $this->hasFilter = $this->hasFilter($this->filters);

        // get the criteria given the request (could pass filters instead?)
        $query = $this->buildCriteria($request);

        // apply the filters to the query
        // get the entities and paginate
        $entities = $query->paginate($this->rpp);

        return view('entities.index')
            ->with([
                'rpp' => $this->rpp,
                'sortBy' => $this->sortBy,
                'sortOrder' => $this->sortOrder,
                'filters' => $this->filters,
                'hasFilter' => $this->hasFilter,
            ])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Set filters attribute.
     * @param Request $request
     * @param array $input
     */
    protected function setFilters(Request $request, array $input): void
    {
        $this->setAttribute('filters', $input, $request);
    }

    /**
     * Set user session attribute.
     *
     * @param string $attribute
     * @param mixed  $value

     */
    protected function setAttribute(string $attribute, $value, Request $request): void
    {
        $request->session()->put($this->prefix . $attribute, $value);
    }

    /**
     * Reset the filtering of entities.
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function reset(Request $request): Response
    {
        // set the filters to empty
        $this->setFilters($request, $this->getDefaultFilters());

        $hasFilter = 0;

        // default
        $query = Entity::where(function ($query) {
            $query->active()
                ->orWhere('created_by', '=', ($this->user ? $this->user->id : null));
        })
            ->orderBy('entity_type_id', 'ASC')
            ->orderBy('name', 'ASC');

        // paginate
        $entities = $query->paginate($this->rpp);

        return redirect()->route('entities.index');
    }

    /**
     * Display a listing of entities by tag.
     *
     * @return Response | string
     *
     * @throws \Throwable
     */
    public function indexTags(Request $request, string $role)
    {
        $this->rpp = 1000;

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $query = Entity::getByTag(ucfirst($role))
            ->where(function ($query) {
                $query->active()
                    ->orWhere('created_by', '=', ($this->user ? $this->user->id : null));
            })
            ->orderBy('entity_type_id', 'ASC')
            ->orderBy('name', 'ASC');
        // paginate
        $entities = $query->paginate($this->rpp);

        return view('entities.index')
            ->with(['role' => $role, 'rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Display a listing of entities by alias.
     *
     * @return Response | string
     *
     * @throws \Throwable
     */
    public function indexAliases(Request $request, string $role)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $this->hasFilter = count($filters);

        $entities = Entity::getByAlias(ucfirst($role))
            ->where(function ($query) {
                $query->active()
                    ->orWhere('created_by', '=', ($this->user ? $this->user->id : null));
            })
            ->orderBy('entity_type_id', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        return view('entities.index')
            ->with(['role' => $role, 'rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $this->hasFilter])
            ->with(compact('entities'))
            ->render();
    }

    /**
     * Display an entity when passed the slug.
     *
     * @return Response | string
     *
     * @throws \Throwable
     */
    public function indexSlug(string $slug)
    {
        $hasFilter = 1;

        $entity = Entity::getBySlug(strtolower($slug))->firstOrFail();

        return view('entities.show')
            ->with(['hasFilter' => $hasFilter])
            ->with(compact('entity'))
            ->render();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response | string
     */
    public function create()
    {
        $entityTypes = EntityType::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entityStatuses = EntityStatus::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $tags = Tag::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $aliases = Alias::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $roles = Role::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $userList = ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('entities.create', compact('entityTypes', 'entityStatuses', 'tags', 'aliases', 'roles', 'userList'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EntityRequest $request, Entity $entity): Response
    {
        $msg = '';

        $input = $request->all();

        $input['slug'] = Str::slug($request->input('slug', '-'));

        $tagArray = $request->input('tag_list', []);
        $aliasArray = $request->input('alias_list', []);
        $syncArray = [];
        $aliasSyncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!DB::table('tags')->where('id', $tag)->get()) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tagType()->associate(TagType::find(1));
                $newTag->save();

                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[] = $newTag->id;

                $msg .= ' Added tag ' . $tag . '.';
            } else {
                $syncArray[$key] = $tag;
            }
        }

        // check the elements in the alias list, and if any don't match, add the alias
        foreach ($aliasArray as $key => $alias) {
            if (!DB::table('aliases')->where('id', $alias)->get()) {
                $newAlias = new Alias();
                $newAlias->name = ucwords(strtolower($alias));
                $newAlias->save();

                $aliasSyncArray[] = $newAlias->id;

                $msg .= ' Added alias ' . $alias . '.';
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

        flash()->success('Success', 'Your entity has been created');

        // return redirect()->route('entities.index');
        return redirect()->route('entities.show', compact('entity'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Entity $entity): View
    {
        $threads = $entity->threads()->paginate($this->rpp);

        return view('entities.show', compact('entity', 'threads'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Entity $entity): View
    {
        $this->middleware('auth');

        $entityTypes = EntityType::orderBy('name', 'ASC')->pluck('name', 'id')->all();
        $entityStatuses = EntityStatus::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        $tags = Tag::orderBy('name')->pluck('name', 'id')->all();
        $aliases = Alias::orderBy('name')->pluck('name', 'id')->all();
        $roles = Role::orderBy('name')->pluck('name', 'id')->all();

        $userList = User::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('entities.edit', compact('entity', 'entityTypes', 'entityStatuses', 'tags', 'aliases', 'roles', 'userList'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Entity $entity, EntityRequest $request): RedirectResponse
    {
        $msg = '';

        $input = $request->all();

        $input['slug'] = Str::slug($request->input('slug', '-'));

        $entity->fill($input)->save();

        if (!$entity->ownedBy(\Auth::user())) {
            $this->unauthorized($request);
        }

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
                $newTag->tagType()->associate(TagType::find(1));
                $newTag->save();

                // log adding of new tag
                Activity::log($newTag, $this->user, 1);

                $syncArray[strtolower($tag)] = $newTag->id;

                $msg .= ' Added tag ' . $tag . '.';
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

                $msg .= ' Added alias ' . $alias . '.';
            } else {
                $aliasSyncArray[$key] = $alias;
            }
        }

        $entity->tags()->sync($syncArray);
        $entity->aliases()->attach($aliasSyncArray);
        $entity->roles()->sync($request->input('role_list', []));

        // add to activity log
        Activity::log($entity, $this->user, 2);

        // flash this message
        flash()->success('Success', $msg);

        //return redirect('entities');
        return redirect()->route('entities.show', compact('entity'));
    }

    /**
     * Remove the specified resource from storage.
     * @throws \Exception
     */
    public function destroy(Entity $entity): RedirectResponse
    {
        // add to activity log
        Activity::log($entity, $this->user, 3);

        $entity->delete();

        return redirect('entities');
    }

    /**
     * Add a photo to an entity.
     */
    public function addPhoto(int $id, Request $request): void
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        $fileName = time() . '_' . $request->file->getClientOriginalName();
        $filePath = $request->file('file')->storeAs('photos', $fileName, 'public');

        // attach to entity
        if ($entity = Entity::find($id)) {
            $photo = $this->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if ($entity->photos && 0 === count($entity->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            // attach to entity
            $entity->addPhoto($photo);
        }
    }

    protected function makePhoto(UploadedFile $file): Photo
    {
        return Photo::named($file->getClientOriginalName())
            ->makeThumbnail();
    }

    /**
     * Mark user as following the entity.
     *
     * @return Response | array
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

        if (!$entity = Entity::find($id)) {
            flash()->error('Error', 'No such entity');

            return back();
        }

        // add the following response
        $follow = new Follow();
        $follow->object_id = $id;
        $follow->user_id = $this->user->id;
        $follow->object_type = 'entity'; // 1 = Attending, 2 = Interested, 3 = Uninterested, 4 = Cannot Attend
        $follow->save();

        Log::info('User ' . $id . ' is following ' . $entity->name);
        // add to activity log
        Activity::log($entity, $this->user, 6);

        // handle the request if ajax
        if ($request->ajax()) {
            return [
                'Message' => 'You are now following the entity - ' . $entity->name,
                'Success' => view('entities.single')
                    ->with(compact('entity'))
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are now following the entity - ' . $entity->name);

        return back();
    }

    /**
     * Mark user as unfollowing the entity.
     * @return Response | array
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
                'Message' => 'You are no longer following the entity - ' . $entity->name,
                'Success' => view('entities.single')
                    ->with(compact('entity'))
                    ->render(),
            ];
        }
        flash()->success('Success', 'You are no longer following the entity - ' . $entity->name);

        return back();
    }

    /**
     * Get the default sort array.
     */
    protected function getDefaultSort(): array
    {
        return ['id', 'desc'];
    }

    protected function unauthorized(EntityRequest $request)
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        \Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }
}
