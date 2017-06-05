<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\GroupRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use App\Group;
use App\Permission;
use App\User;


class GroupsController extends Controller {

	public function __construct(Group $group)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
		$this->group = $group;

		// default list variables
		$this->rpp = 15;
		$this->sortBy = 'created_at';
		$this->sortDirection = 'desc';

		parent::__construct();
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index(Request $request)
	{
 		// updates sort, rpp from request
 		$this->updatePaging($request);

        $groups = Group::orderBy($this->sortBy, $this->sortDirection)->paginate($this->rpp);

        return view('groups.index')
        	->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortDirection' => $this->sortDirection])
        	->with(compact('groups'));
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
 		if ($request->input('sort_direction')) {
 			$this->sortDirection = $request->input('sort_direction');
 		};

 		// set results per page
 		if ($request->input('rpp')) {
 			$this->rpp = $request->input('rpp');
 		};
	}



	/**
	 * Display a listing of groups by user
	 *
	 * @return Response
	 */
	public function indexUsers($user)
	{
 
  		// updates sort, rpp from request
 		$this->updatePaging($request);

		$groups = Group::getByUser(ucfirst($user))
					->orderBy('name', 'ASC')
					->get();

		return view('groups.index')
        	->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortDirection' => $this->sortDirection])
			->with(compact('groups', 'user'));
	}


	/**
	 * Display a listing of groups by permission
	 *
	 * @return Response
	 */
	public function indexPermissions($permission)
	{
 
		$groups = Group::getByPermission(ucfirst($permission))
					->orderBy('name', 'ASC')
					->get();

		return view('groups.index', compact('groups', 'permission'));
	}

	/**
	 * Filter the list of groups
	 *
	 * @return Response
	 */
	public function filter(Request $request, GroupFilters $filters)
	{
		//$groups = array();
		$groups = $this->group->active();

 		// check request for passed filter values
 		if ($request->input('filter_permission'))
 		{
 			 	//		dd('filter role');
 			$permission = $request->input('filter_permission');
			$groups = Group::getByPermission(ucfirst($permission))
						->orderBy('name', 'ASC')
						->get();
 		};


   		if ($request->input('filter_name'))
 		{
 			$name = $request->input('filter_name');
			$groups = Group::where('name', $name)->get();
 		}

 		// revisit filtering here
		// $groups->filter($filters);
		// $groups->get();

		return view('groups.index', compact('groups', 'permission'));
	}

	/**
	 * Reset the filtering of groups
	 *
	 * @return Response
	 */
	public function reset()
	{
 		$groups = Group::orderBy('name', 'ASC')
						->get();

		return view('groups.index', compact('groups'));
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{

		$permissions = Permission::orderBy('name')->lists('name','id')->all();
		$users = User::orderBy('name')->lists('name','id')->all();

		return view('groups.create', compact('permissions','users'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	 public function store(GroupRequest $request, Group $group)
 	{

 		$msg = "";
 		
 		$input = $request->all();

		$group = $group->create($input);

		$group->permissions()->attach($request->input('permission_list',[]));
		$group->users()->attach($request->input('user_list',[]));

		flash()->success('Success', 'Your group has been created');

 		return redirect()->route('groups.index');
 	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show(Group $group)
	{
		return view('groups.show', compact('group'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit(Group $group)
	{
		$this->middleware('auth');

		$permissions = Permission::orderBy('name')->lists('name','id')->all();
		$users = User::orderBy('name')->lists('name','id')->all();

		return view('groups.edit', compact('group','permissions','users'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(Group $group, Request $request)
	{
		$msg = '';
		
		$group->fill($request->input())->save();

		// do a check here that the user can update groups
		/*
		if (!$group->ownedBy(\Auth::user()))
		{
			$this->unauthorized($request); 
		};
		*/


		$group->permissions()->sync($request->input('permission_list', []));

		$group->users()->sync($request->input('user_list', []));

		return redirect('groups');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy(Group $group)
	{
		$group->delete();

		return redirect('groups');
	}


}
