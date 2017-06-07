<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Illuminate\Http\Request;
use Carbon\Carbon;

use DB;
use Log;
use App\Permission;
use App\Group;


class PermissionsController extends Controller {

	public function __construct(Permission $permission)
	{
		$this->middleware('auth', ['only' => array('create', 'edit', 'store', 'update')]);
		$this->permission = $permission;

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

        $permissions = Permission::orderBy($this->sortBy, $this->sortDirection)->paginate($this->rpp);

        return view('permissions.index')
        	->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortDirection' => $this->sortDirection])
        	->with(compact('permissions'));
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
	 * Display a listing of permissions by group
	 *
	 * @return Response
	 */
	public function indexGroups($group)
	{
 
  		// updates sort, rpp from request
 		$this->updatePaging($request);

		$permissions = Permission::getByUser(ucfirst($user))
					->orderBy('name', 'ASC')
					->get();

		return view('permissions.index')
        	->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortDirection' => $this->sortDirection])
			->with(compact('permissions', 'group'));
	}

	/**
	 * Filter the list of permissions
	 *
	 * @return Response
	 */
	public function filter(Request $request, PermissionFilters $filters)
	{
		// refactor this to filter by an array of $filter params that contain all the passed filters
		
		//$permissions = array();
		$permissions = $this->permission->active();

 		// check request for passed filter values
 		if ($request->input('filter_group'))
 		{
 			$group = $request->input('filter_group');
			$permissions = Permission::getByGroup(ucfirst($group))
						->orderBy('name', 'ASC')
						->get();
 		};


   		if ($request->input('filter_name'))
 		{
 			$name = $request->input('filter_name');
			$permissions = Permission::where('name', $name)->get();
 		}

 		// revisit filtering here
		// $permissions->filter($filters);
		//$permissions->get();

		return view('permissions.index', compact('permissions', 'role', 'tag','name'));
	}

	/**
	 * Reset the filtering of permissions
	 *
	 * @return Response
	 */
	public function reset()
	{
 		$permissions = Permission::orderBy('name', 'ASC')
					->get();

		return view('permissions.index', compact('permissions', 'group'));
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		$groups = Group::orderBy('name','ASC')->pluck('name','id')->all();

		return view('permissions.create',compact('groups'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	 public function store(PermissionRequest $request, Permission $permission)
 	{

 		$msg = "";
 		
 		$input = $request->all();

		$permission->groups()->attach($request->input('group_list',[]));

		flash()->success('Success', 'Your permission has been created');

 		return redirect()->route('permissions.index');
 	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show(Permission $permission)
	{
		return view('permissions.show', compact('permission'));
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit(Permission $permission)
	{
		$this->middleware('auth');

		$groups = Group::orderBy('name','ASC')->pluck('name','id')->all();

		return view('permissions.edit', compact('permission', 'groups'));
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(Permission $permission, Request $request)
	{
		$msg = '';
		
		$permission->fill($request->input())->save();

		// check to see if the user can update the permission here
		/*
		if (!$permission->ownedBy(\Auth::user()))
		{
			$this->unauthorized($request); 
		};
		*/

		$permission->groups()->sync($request->input('group_list', []));

		return redirect('permissions');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy(Permission $permission)
	{
		$permission->delete();

		return redirect('permissions');
	}


}
