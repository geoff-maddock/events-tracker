<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\EntityRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use App\Entity;
use App\EntityFilters;
use App\EntityType;
use App\EntityStatus;
use App\Tag;
use App\Role;
use App\Location;
use App\Photo;
use App\Follow;

class EntitiesController extends Controller {

	public function __construct(Entity $entity)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
		$this->entity = $entity;

		parent::__construct();
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(EntityFilters $filters)
	{
		$is_filtering = 0;

		// get all active entites plus those created by the logged in user, ordered by type and name

		// base criteria
		$entities = $this->entity->active()
		->orWhere('created_by','=',($this->user ? $this->user->id : NULL))
		->orderBy('entity_type_id', 'ASC')
		->orderBy('name', 'ASC');

		$entities->filter($filters);

		/*
		if ($request->has('name'))
		{	$name = $request->name;
			$this->entity->where('name', $name);
		}
		*/

		$entities = $this->entity->get();

		return view('entities.index', compact('entities'));
	}


	/**
	 * Display a listing of entities by type
	 *
	 * @return Response
	 */
	public function indexTypes($type)
	{

		$entities = Entity::ofType(ucfirst($type))
					->where(function($query)
					{
						$query->active()
						->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
					})
					->orderBy('entity_type_id', 'ASC')->orderBy('name', 'ASC')
					->get();

		return view('entities.index', compact('entities', 'type'));
	}

	/**
	 * Display a listing of entities by role
	 *
	 * @return Response
	 */
	public function indexRoles($role)
	{
 
		$entities = Entity::getByRole(ucfirst($role))
					->where(function($query)
					{
						$query->active()
						->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
					})
					->orderBy('entity_type_id', 'ASC')
					->orderBy('name', 'ASC')
					->get();

		return view('entities.index', compact('entities', 'role'));
	}

	/**
	 * Filter the list of entities
	 *
	 * @return Response
	 */
	public function filter(Request $request, EntityFilters $filters)
	{
		//$entities = array();
		$entities = $this->entity->active();

 		// check request for passed filter values
 		if ($request->input('filter_role'))
 		{
 			 	//		dd('filter role');
 			$role = $request->input('filter_role');
			$entities = Entity::getByRole(ucfirst($role))
						->where(function($query)
						{
							$query->active()
							->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
						})
						->orderBy('entity_type_id', 'ASC')
						->orderBy('name', 'ASC')
						->get();
 		};

  		if ($request->input('filter_tag'))
 		{
 			$tag = $request->input('filter_tag');
			$entities = Entity::getByTag(ucfirst($tag))
						->where(function($query)
						{
							$query->active()
							->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
						})
						->orderBy('entity_type_id', 'ASC')
						->orderBy('name', 'ASC')
						->get();
 		}

   		if ($request->input('filter_name'))
 		{
 			$name = $request->input('filter_name');
			$entities = Entity::where('name', $name)->get();
 		}

 		// revisit filtering here
		// $entities->filter($filters);
		//$entities->get();

		return view('entities.index', compact('entities', 'role', 'tag','name'));
	}

	/**
	 * Reset the filtering of entities
	 *
	 * @return Response
	 */
	public function reset()
	{
 
		$entities = Entity::getByRole(ucfirst($role))
					->where(function($query)
					{
						$query->active()
						->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
					})
					->orderBy('entity_type_id', 'ASC')
					->orderBy('name', 'ASC')
					->get();

		return view('entities.index', compact('entities', 'role'));
	}

	/**
	 * Display a listing of entities by tag
	 *
	 * @return Response
	 */
	public function indexTags($role)
	{
 
		$entities = Entity::getByTag(ucfirst($role))
					->where(function($query)
					{
						$query->active()
						->orWhere('created_by','=',($this->user ? $this->user->id : NULL));
					})
					->orderBy('entity_type_id', 'ASC')
					->orderBy('name', 'ASC')
					->get();

		return view('entities.index', compact('entities', 'role'));
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
		$roles = Role::orderBy('name','ASC')->pluck('name','id')->all();

		return view('entities.create',compact('entityTypes','entityStatuses','tags','roles'));
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
		$syncArray = array();

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
		}

		$entity = $entity->create($input);

		$entity->tags()->attach($syncArray);
		$entity->roles()->attach($request->input('role_list',[]));

		flash()->success('Success', 'Your entity has been created');

 		return redirect()->route('entities.index');
 	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show(Entity $entity)
	{
		return view('entities.show', compact('entity'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit(Entity $entity)
	{
		$this->middleware('auth');

		$entityTypes =  EntityType::orderBy('name','ASC')->pluck('name', 'id')->all();
		$entityStatuses = EntityStatus::orderBy('name','ASC')->pluck('name', 'id')->all();

		$tags = Tag::orderBy('name')->pluck('name','id')->all();
		$roles = Role::orderBy('name')->pluck('name','id')->all();

		return view('entities.edit', compact('entity','entityTypes', 'entityStatuses','tags','roles'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(Entity $entity, Request $request)
	{
		$msg = '';
		
		$entity->fill($request->input())->save();

		if (!$entity->ownedBy(\Auth::user()))
		{
			$this->unauthorized($request); 
		};

		$tagArray = $request->input('tag_list',[]);

		$syncArray = array();

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
		}

		$entity->tags()->sync($syncArray);

		$entity->roles()->sync($request->input('role_list', []));

		return redirect('entities');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
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

		//$photo = Photo::fromForm($request->file('file'));
		$photo = $this->makePhoto($request->file('file'));
		$photo->save();

		// attach to entity
		$entity = Entity::find($id);
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
