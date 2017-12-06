<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\EntityRequest;
use phpDocumentor\Reflection\Types\Mixed;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use Auth;
use App\Entity;
use App\EntityFilters;
use App\EntityType;
use App\EntityStatus;
use App\Tag;
use App\Alias;
use App\Role;
use App\Photo;
use App\Follow;

class EntitiesController extends Controller {

    // define a list of variables
    protected $prefix;
    protected $rpp;
    protected $page;
    protected $sort;
    protected $sortBy;
    protected $sortOrder;
    protected $defaultCriteria;
    protected $hasFilter;

	public function __construct(Entity $entity)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
		$this->entity = $entity;

        // prefix for session storage
        $this->prefix = 'app.entities.';

        // default list variables
        $this->rpp = 5;
        $this->page = 1;
        $this->sort = array('name', 'desc');
        $this->sortBy = 'name';
        $this->sortOrder = 'asc';
        $this->defaultCriteria = NULL;
        $this->hasFilter = 1;
		parent::__construct();
	}


    /**
     * Update the page list parameters from the request
     *
     */
    protected function updatePaging($request)
    {
        // set sort by column
        if ($request->input('sort_by')) {
            $this->sortBy = $request->input('sort_by');
        };

        // set sort direction
        if ($request->input('sort_order')) {
            $this->sortOrder = $request->input('sort_order');
        };

        // set results per page
        if ($request->input('rpp')) {
            $this->rpp = $request->input('rpp');
        };
    }


    /**
     * Gets the reporting options from the request and saves to session
     *
     * @param Request $request
     */
    public function getReportingOptions(Request $request)
    {
        foreach (array('page', 'rpp', 'sort', 'criteria') as $option)
        {
            if (!$request->has($option))
            {
                continue;
            }
            switch ($option)
            {
                case 'sort':
                    $value = array
                    (
                        $request->input($option),
                        $request->input('sort_order', 'asc'),
                    );
                    break;
                default:
                    $value = $request->input($option);
                    break;
            }
            call_user_func
            (
                array($this, sprintf('set%s', ucwords($option))),
                $value
            );
        }
    }

    /**
     * Get user session attribute
     *
     * @param String $attribute
     * @param Mixed $default
     * @param Request $request
     * @return Mixed
     */
    protected function getAttribute($attribute, $default = null, Request $request)
    {
        return $request->session()
            ->get($this->prefix.$attribute, $default);
    }

    /**
     * Get session filters
     *
     * @return Array
     */
    protected function getFilters(Request $request)
    {
        return $this->getAttribute('filters', $this->getDefaultFilters(), $request);
    }



    /**
     * Get the current page for this module
     *
     * @return integer
     */
    protected function getPage()
    {
        return $this->getAttribute('page', 1);
    }

    /**
     * Get the current results per page
     *
     * @param Request $request
     * @return integer
     */
    protected function getRpp(Request $request)
    {
        //$rpp = $request->session()->get('filters', $this->rpp);
        return $this->getAttribute('rpp', $this->rpp);
    }
    /**
     * Get the sort order and column
     *
     * @return array
     */
    protected function getSort(Request $request)
    {
        return $this->getAttribute('sort', $this->getDefaultSort());
    }



    /**
     * Get the default sort array
     *
     * @return array
     */
    protected function getDefaultSort()
    {
        return array('id', 'desc');
    }


    /**
     * Get the default filters array
     *
     * @return array
     */
    protected function getDefaultFilters()
    {
        return array();
    }

    /**
     * Set user session attribute
     *
     * @param String $attribute
     * @param Mixed $value
     * @param Request $request
     * @return Mixed
     */
    protected function setAttribute($attribute, $value, Request $request)
    {
        return $request->session()
            ->put($this->prefix.$attribute, $value);
    }

    /**
     * Set filters attribute
     *
     * @param array $input
     * @return array
     */
    protected function setFilters(Request $request, array $input)
    {
        // example: $input = array('filter_tag' => 'role', 'filter_name' => 'xano');
        return $this->setAttribute('filters', $input, $request);
    }
    /**
     * Set criteria.
     *
     * @param array $input
     * @return string
     */
    protected function setCriteria($input)
    {
        $this->criteria = $input;
        return $this->criteria;
    }
    /**
     * Set page attribute
     *
     * @param integer $input
     * @return integer
     */
    protected function setPage($input)
    {
        return $this->setAttribute('page', $input);
    }
    /**
     * Set results per page attribute
     *
     * @param integer $input
     * @return integer
     */
    protected function setRpp($input)
    {
        return $this->setAttribute('rpp', 5);
    }
    /**
     * Set sort order attribute
     *
     * @param array $input
     * @return array
     */
    protected function setSort(array $input)
    {
        return $this->setAttribute('sort', $input);
    }


    /**
     * Display a listing of the resource.
     *
     * @return Response
     * @throws \Throwable
     */
	public function index(Request $request, EntityFilters $filters)
    {
        $hasFilter = 1;

        // get all active entites plus those created by the logged in user, ordered by type and name

        // updates sort, rpp from request
        $this->updatePaging($request);

        // get filters from session
        $filters = $this->getFilters($request);

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
     * Builds the criteria from the session
     *
     * @return $query
     */
    public function buildCriteria(Request $request)
    {
        $hasFilter = 1;

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // base criteria
        $query = $this->entity->active()
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

            // add to filters array
            $filters['filter_tag'] = $tag;
        }

        if (!empty($filters['filter_role']))
        {
            $role = $filters['filter_role'];
            // add has clause
            $query->whereHas('roles', function($q) use ($role)
            {
                $q->where('slug','=', strtolower($role));
            });

            // add to filters array
            $filters['filter_role'] = $role;

        };

        // change this - should be seperate
        if (!empty($filters['filter_rpp'])) {
            $this->rpp = $filters['filter_rpp'];
        }

        return $query;
    }

	/**
	 * Display a listing of entities by type
	 *
	 * @return Response
	 */
	public function indexTypes($type)
	{
        $hasFilter = 1;

		$entities = Entity::ofType(ucfirst($type))
					->where(function($query)
					{
						$query->active()
						->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
					})
					->orderBy('entity_type_id', 'ASC')->orderBy('name', 'ASC')
                    ->paginate();

		return view('entities.index')
            ->with(['type' => $type, 'rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter, ])
            ->with(compact('entities'))
            ->render();
	}


	/**
	 * Display a listing of entities by role
	 *
	 * @return Response
	 */
	public function indexRoles($role)
	{
        $hasFilter = 1;

		$entities = Entity::getByRole(ucfirst($role))
					->where(function($query)
					{
						$query->active()
						->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
					})
					->orderBy('entity_type_id', 'ASC')
					->orderBy('name', 'ASC')
					->paginate();

        return view('entities.index')
            ->with(['role' => $role, 'rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('entities'))
            ->render();

	}

	/**
	 * Filter the list of entities
	 *
	 * @return Response
	 */
	public function filter(Request $request, EntityFilters $filters)
	{
	    // EntityFilters is a class that defines
        // indicate if there are any filters
        $hasFilter = 1;

        // get all the filters from the session
        $filters = $this->getFilters($request);

        // updates sort, rpp from request - TODO add other filters?
        $this->updatePaging($request);

        // base criteria
        $query = $this->buildCriteria($request);

        // add the criteria from the session
 		// check request for passed filter values

        if (!empty($request->input('filter_name')))
        {
            // getting name from the request
            $name = $request->input('filter_name');
            $query->where('name', 'like', '%'.$name.'%')
                ->orWherehas('aliases', function($q) use ($name)
                {
                    $q->where('name','=', ucfirst($name));
                });
            // add to filters array
            $filters['filter_name'] = $name;
        }

 		if (!empty($request->input('filter_role')))
 		{
 			$role = $request->input('filter_role');
            // add has clause
 			$query->whereHas('roles', function($q) use ($role)
            {
                $q->where('slug','=', strtolower($role));
            });

            // add to filters array
            $filters['filter_role'] = $role;
 		};

  		if (!empty($request->input('filter_tag')))
 		{
 			$tag = $request->input('filter_tag');
			$query->whereHas('tags', function($q) use ($tag)
            {
                $q->where('name','=', ucfirst($tag));
            });

            // add to filters array
            $filters['filter_tag'] = $tag;
 		}

  		if (!empty($request->input('filter_alias')))
 		{
 			$alias = $request->input('filter_alias');
			$query = Entity::getByAlias(ucfirst($alias))
						->where(function($query)
						{
							$query->active()
							->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
						})
						->orderBy('entity_type_id', 'ASC')
						->orderBy('name', 'ASC');
            // add to filters array
            $filters['filter_alias'] = $alias;
 		}

        // change this - should be seperate
        if (!empty($request->input('filter_rpp')))
        {
            $this->rpp = $request->input('filter_rpp');
            $filters['filter_rpp'] = $this->rpp;
        }

        // save filters to session
        $this->setFilters($request, $filters);

        // apply the filters to the query
        // get the entities and paginate
        $entities = $query->paginate($this->rpp);

		return view('entities.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'filters' => $filters, 'hasFilter' => $hasFilter,
                'filter_name' => isset($filters['filter_name']) ? $filters['filter_name'] : NULL ,  // there should be a better way to do this...
                'filter_role' => isset($filters['filter_role']) ? $filters['filter_role'] : NULL,
                'filter_tag' => isset($filters['filter_tag']) ? $filters['filter_tag'] : NULL
                ])
            ->with(compact('entities', 'role', 'tag', 'alias', 'name'))
            ->render();

	}

	/**
	 * Reset the filtering of entities
	 *
	 * @return Response
	 */
	public function reset(Request $request)
	{
        // doesn't have filter, but temp
        $hasFilter = 1; 
        // set the filters to empty
        $this->setFilters($request, $this->getDefaultFilters());
 
        //dd($request->session());

        // default 
		$query = Entity::where(function($query)
					{
						$query->active()
						->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
					})
					->orderBy('entity_type_id', 'ASC')
					->orderBy('name', 'ASC');

        // paginate
        $entities = $query->paginate($this->rpp);

        return view('entities.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('entities'))
            ->render();

	}

	/**
	 * Display a listing of entities by tag
	 *
	 * @return Response
	 */
	public function indexTags($role)
	{
        $hasFilter = 1;
        $this->rpp = 1000;

		$query = Entity::getByTag(ucfirst($role))
					->where(function($query)
					{
						$query->active()
						->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
					})
					->orderBy('entity_type_id', 'ASC')
					->orderBy('name', 'ASC');
        // paginate
        $entities = $query->paginate($this->rpp);

        return view('entities.index')
            ->with(['role' => $role, 'rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('entities'))
            ->render();
	}

	/**
	 * Display a listing of entities by alias
	 *
	 * @return Response
	 */
	public function indexAliases($role)
	{
        $hasFilter = 1;
        
		$entities = Entity::getByAlias(ucfirst($role))
					->where(function($query)
					{
						$query->active()
						->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
					})
					->orderBy('entity_type_id', 'ASC')
					->orderBy('name', 'ASC')
					->get();

        return view('entities.index')
            ->with(['role' => $role, 'rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder, 'hasFilter' => $hasFilter])
            ->with(compact('entities'))
            ->render();
	}

    /**
     * Display an entity when passed the slug
     *
     * @return Response
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
		$entityTypes = EntityType::orderBy('name','ASC')->pluck('name', 'id')->all();
		$entityStatuses = EntityStatus::orderBy('name','ASC')->pluck('name', 'id')->all();

		$tags = Tag::orderBy('name','ASC')->pluck('name','id')->all();
		$aliases = Alias::orderBy('name','ASC')->pluck('name','id')->all();
		$roles = Role::orderBy('name','ASC')->pluck('name','id')->all();

		return view('entities.create',compact('entityTypes','entityStatuses','tags','aliases','roles'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	 public function store(EntityRequest $request, Entity $entity)
 	{

 		$msg = "";
 		
 		$input = $request->all();

		$tagArray = $request->input('tag_list',[]);
		$aliasArray = $request->input('alias_list',[]);
		$syncArray = array();
		$aliasSyncArray = array();

		// check the elements in the tag list, and if any don't match, add the tag
		foreach ($tagArray as $key => $tag)
		{

			if (!DB::table('tags')->where('id', $tag)->get())
			{
				$newTag = new Tag;
				$newTag->name = ucwords(strtolower($tag));
				$newTag->tag_type_id = 1;
				$newTag->save();

				$syncArray[] = $newTag->id;

				$msg .= ' Added tag '.$tag.'.';
			} else {
				$syncArray[$key] = $tag;
			};
		};

		// check the elements in the alias list, and if any don't match, add the alias
		foreach ($aliasArray as $key => $alias)
		{

			if (!DB::table('aliases')->where('id', $alias)->get())
			{
				$newAlias = new Alias;
				$newAlias->name = ucwords(strtolower($alias));
				$newAlias->save();

				$aliasSyncArray[] = $newAlias->id;

				$msg .= ' Added alias '.$alias.'.';
			} else {
				$aliasSyncArray[$key] = $alias;
			};
		}

		$entity = $entity->create($input);

		$entity->tags()->attach($syncArray);
		$entity->aliases()->attach($aliasSyncArray);
		$entity->roles()->attach($request->input('role_list',[]));

		flash()->success('Success', 'Your entity has been created');

 		return redirect()->route('entities.index');
 	}

    /**
     * Display the specified resource.
     *
     * @param Entity $entity
     * @return Response
     * @internal param int $id
     */
	public function show(Entity $entity)
	{
		return view('entities.show', compact('entity'));
	}

    /**
     * Show the form for editing the specified resource.
     *
     * @param Entity $entity
     * @return Response
     * @internal param int $id
     */
	public function edit(Entity $entity)
	{
		$this->middleware('auth');

		$entityTypes =  EntityType::orderBy('name','ASC')->pluck('name', 'id')->all();
		$entityStatuses = EntityStatus::orderBy('name','ASC')->pluck('name', 'id')->all();

		$tags = Tag::orderBy('name')->pluck('name','id')->all();
		$aliases = Alias::orderBy('name')->pluck('name','id')->all();
		$roles = Role::orderBy('name')->pluck('name','id')->all();

		return view('entities.edit', compact('entity','entityTypes', 'entityStatuses','tags','aliases','roles'));
	}

    /**
     * Update the specified resource in storage.
     *
     * @param Entity $entity
     * @param EntityRequest|Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @internal param int $id
     */
	public function update(Entity $entity, EntityRequest $request)
	{
		$msg = '';
		
		$entity->fill($request->input())->save();

		if (!$entity->ownedBy(\Auth::user()))
		{
			$this->unauthorized($request); 
		};

        // if we got this far, it worked
        $msg = 'Updated entity. ';

		$tagArray = $request->input('tag_list',[]);
		$aliasArray = $request->input('alias_list',[]);

		$syncArray = array();
		$aliasSyncArray = array();

		// check the elements in the tag list, and if any don't match, add the tag
		foreach ($tagArray as $key => $tag)
		{
            if (!Tag::find($tag))
            {
                $newTag = new Tag;
                $newTag->name = ucwords(strtolower($tag));
                $newTag->tag_type_id = 1;
                $newTag->save();

                $syncArray[strtolower($tag)] = $newTag->id;

                $msg .= ' Added tag '.$tag.'.';
            } else {
                $syncArray[$key] = $tag;
            };
		}

		// check the elements in the alias list, and if any don't match, add the alias
		foreach ($aliasArray as $key => $alias)
		{
            if (!Alias::find($alias))
			{
				$newAlias = new Alias;
				$newAlias->name = ucwords(strtolower($alias));
				$newAlias->save();

				$aliasSyncArray[strtolower($alias)] = $newAlias->id;

				$msg .= ' Added alias '.$alias.'.';
			} else {
				$aliasSyncArray[$key] = $alias;
			};
		}

		$entity->tags()->sync($syncArray);
		$entity->aliases()->attach($aliasSyncArray);
		$entity->roles()->sync($request->input('role_list', []));

        // flash this message
        flash()->success('Success',  $msg);

		return redirect('entities');
	}

    /**
     * Remove the specified resource from storage.
     *
     * @param Entity $entity
     * @return Response
     * @internal param int $id
     */
	public function destroy(Entity $entity)
	{
		$entity->delete();

		return redirect('entities');
	}


	/**
	 * Add a photo to an entity
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function addPhoto($id, Request $request)
	{
		$this->validate($request, [
			'file' =>'required|mimes:jpg,jpeg,png,gif'
		]);

        // attach to entity
        $entity = Entity::find($id);

		$photo = $this->makePhoto($request->file('file'));

        // count existing photos, and if zero, make this primary
        if (count($entity->photos) == 0)
        {
            $photo->is_primary=1;
        };

		$photo->save();

		// attach to entity
		$entity->addPhoto($photo);
	}
	
	protected function makePhoto(UploadedFile $file)
	{
		return Photo::named($file->getClientOriginalName())
			->move($file);
	}

	/**
	 * Mark user as following the entity
	 *
	 * @return Response
	 */
	public function follow($id, Request $request)
	{
		// check if there is a logged in user
		if (!$this->user)
		{
			flash()->error('Error',  'No user is logged in.');
			return back();
		};

		if (!$entity = Entity::find($id))
		{
			flash()->error('Error',  'No such entity');
			return back();
		};

		// add the following response
		$follow = new Follow;
		$follow->object_id = $id;
		$follow->user_id = $this->user->id;
		$follow->object_type = 'entity'; // 1 = Attending, 2 = Interested, 3 = Uninterested, 4 = Cannot Attend
		$follow->save();

     	Log::info('User '.$id.' is following '.$entity->name);

		flash()->success('Success',  'You are now following the entity - '.$entity->name);

		return back();

	}

	/**
	 * Mark user as unfollowing the entity.
	 *
	 * @return Response
	 */
	public function unfollow($id, Request $request)
	{

		// check if there is a logged in user
		if (!$this->user)
		{
			flash()->error('Error',  'No user is logged in.');
			return back();
		};

		if (!$entity = Entity::find($id))
		{
			flash()->error('Error',  'No such entity');
			return back();
		};

		// delete the follow
		$response = Follow::where('object_id','=', $id)->where('user_id','=',$this->user->id)->where('object_type','=','entity')->first();
		$response->delete();

		flash()->success('Success',  'You are no longer following the entity.');

		return back();

	}
}
