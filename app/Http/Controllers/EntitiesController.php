<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Alias;
use App\Entity;
use App\EntityStatus;
use App\EntityType;
use App\Follow;
use App\Http\Requests\EntityRequest;
use App\Photo;
use App\Role;
use App\Tag;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class EntitiesController extends Controller
{
    protected $prefix;
    protected $defaultRpp;
    protected $defaultSortBy;
    protected $defaultSortOrder;
    protected $rpp;
    protected $page;
    protected $sort;
    protected $sortBy;
    protected $sortOrder;
    protected $defaultCriteria;
    protected $filters;
    protected $hasFilter;

    public function __construct(Entity $entity)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);
        $this->entity = $entity;

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
        // get all active entites plus those created by the logged in user, ordered by type and name

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

        $hasFilter = \count($filters);

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
     *
     * @param $filters
     */
    protected function getPaging($filters): void
    {
        $this->sortBy = $filters['sortBy'] ?? $this->defaultSortBy;
        $this->sortOrder = $filters['sortOrder'] ?? $this->defaultSortOrder;
        $this->rpp = $filters['rpp'] ?? $this->rpp;
    }

    /**
     * Update the page list parameters from the request.
     */
    protected function updatePaging($request)
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
        if ($request->input('rpp')) {
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
        return $this->getAttribute('filters', $this->getDefaultFilters(), $request);
    }

    /**
     * Get user session attribute.
     *
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function getAttribute($attribute, $default = null, Request $request)
    {
        return $request->session()
            ->get($this->prefix.$attribute, $default);
    }

    /**
     * Get the default filters array.
     *
     * @return mixed
     */
    protected function getDefaultFilters()
    {
        return [];
    }

    /**
     * Gets the base criteria.
     *
     * @return $query
     */
    public function getBaseCriteria(Request $request)
    {
        return $this->entity->active()
            ->orderBy('entity_type_id', 'ASC')
            ->orderBy($this->sortBy, $this->sortOrder);
    }

    /**
     * Builds the criteria from the session.
     *
     * @return $query
     */
    public function buildCriteria(Request $request)
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
            $query->where('name', 'like', '%'.$name.'%');
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
     *
     * @param $filters
     */
    public function hasFilter($filters): bool
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
     *
     * @return array
     */
    protected function setFilters(Request $request, array $input)
    {
        return $this->setAttribute('filters', $input, $request);
    }

    /**
     * Set user session attribute.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function setAttribute($attribute, $value, Request $request)
    {
        return $request->session()->put($this->prefix.$attribute, $value);
    }

    /**
     * Reset the filtering of entities.
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function reset(Request $request)
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
    public function indexTags(Request $request, $role)
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
    public function indexAliases(Request $request, $role)
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
     * @return Response
     *
     * @throws \Throwable
     */
    public function indexSlug($slug)
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
     * @return Response
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
     *
     * @return Response
     */
    public function store(EntityRequest $request, Entity $entity)
    {
        $msg = '';

        $input = $request->all();

        $input['slug'] = str_slug($request->input('slug', '-'));

        $tagArray = $request->input('tag_list', []);
        $aliasArray = $request->input('alias_list', []);
        $syncArray = [];
        $aliasSyncArray = [];

        // check the elements in the tag list, and if any don't match, add the tag
        foreach ($tagArray as $key => $tag) {
            if (!DB::table('tags')->where('id', $tag)->get()) {
                $newTag = new Tag();
                $newTag->name = ucwords(strtolower($tag));
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
            if (!DB::table('aliases')->where('id', $alias)->get()) {
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

        flash()->success('Success', 'Your entity has been created');

        // return redirect()->route('entities.index');
        return redirect()->route('entities.show', compact('entity'));
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     *
     * @internal param int $id
     */
    public function show(Entity $entity)
    {
        $threads = $entity->threads()->paginate($this->rpp);

        return view('entities.show', compact('entity', 'threads'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     *
     * @internal param int $id
     */
    public function edit(Entity $entity)
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
     *
     * @param EntityRequest|Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @internal param int $id
     */
    public function update(Entity $entity, EntityRequest $request)
    {
        $msg = '';

        $input = $request->all();

        $input['slug'] = str_slug($request->input('slug', '-'));

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

        // flash this message
        flash()->success('Success', $msg);

        //return redirect('entities');
        return redirect()->route('entities.show', compact('entity'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     *
     * @internal param int $id
     *
     * @throws \Exception
     */
    public function destroy(Entity $entity)
    {
        // add to activity log
        Activity::log($entity, $this->user, 3);

        $entity->delete();

        return redirect('entities');
    }

    /**
     * Add a photo to an entity.
     *
     * @param int $id
     */
    public function addPhoto($id, Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:jpg,jpeg,png,gif',
        ]);

        // attach to entity
        if ($entity = Entity::find($id)) {
            $photo = $this->makePhoto($request->file('file'));

            // count existing photos, and if zero, make this primary
            if ($entity->photos && 0 == count($entity->photos)) {
                $photo->is_primary = 1;
            }

            $photo->save();

            // attach to entity
            $entity->addPhoto($photo);
        }
    }

    protected function makePhoto(UploadedFile $file)
    {
        return Photo::named($file->getClientOriginalName())
            ->move($file);
    }

    /**
     * Mark user as following the entity.
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function follow($id, Request $request)
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

        return back();
    }

    /**
     * Mark user as unfollowing the entity.
     *
     * @param $id
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function unfollow($id, Request $request)
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
     * Get the current page for this module.
     *
     * @return int
     */
    protected function getPage()
    {
        return $this->getAttribute('page', 1);
    }

    /**
     * Set page attribute.
     *
     * @param int $input
     *
     * @return int
     */
    protected function setPage($input)
    {
        return $this->setAttribute('page', $input);
    }

    /**
     * Get the current results per page.
     *
     * @return int
     */
    protected function getRpp(Request $request)
    {
        //$rpp = $request->session()->get('filters', $this->rpp);
        return $this->getAttribute('rpp', $this->rpp);
    }

    /**
     * Set results per page attribute.
     *
     * @param int $input
     *
     * @return int
     */
    protected function setRpp($input)
    {
        return $this->setAttribute('rpp', 5);
    }

    /**
     * Get the sort order and column.
     *
     * @return array
     */
    protected function getSort(Request $request)
    {
        return $this->getAttribute('sort', $this->getDefaultSort());
    }

    /**
     * Set sort order attribute.
     *
     * @return array
     */
    protected function setSort(array $input)
    {
        return $this->setAttribute('sort', $input);
    }

    /**
     * Get the default sort array.
     *
     * @return array
     */
    protected function getDefaultSort()
    {
        return ['id', 'desc'];
    }

    /**
     * Set criteria.
     *
     * @param array $input
     *
     * @return array
     */
    protected function setCriteria($input)
    {
        $this->criteria = $input;

        return $this->criteria;
    }
}
