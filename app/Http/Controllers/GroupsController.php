<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Group;
use App\Models\Permission;
use App\Models\User;

class GroupsController extends Controller
{
    protected string $prefix;

    protected int $rpp;

    protected int $defaultRpp;

    protected int $defaultGridRpp;

    protected string $defaultSortBy;

    protected string $defaultSortOrder;

    protected int $gridRpp;

    protected int $page;

    protected array $sort;

    protected string $sortBy;

    protected string $sortOrder;

    protected array $filters;

    protected bool $hasFilter;

    public function __construct()
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);

        // default list variables
        $this->rpp = 15;
        $this->sortBy = 'created_at';
        $this->sortOrder = 'desc';

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

        $groups = Group::orderBy($this->sortBy, $this->sortOrder)->paginate($this->rpp);

        return view('groups.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder])
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

        // set sort order
        if ($request->input('sort_order')) {
            $this->sortOrder = $request->input('sort_order');
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
    public function indexUsers(Request $request, User $user)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        $groups = Group::getByUser(ucfirst($user))
                    ->orderBy('name', 'ASC')
                    ->get();

        return view('groups.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder])
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
    public function filter(Request $request)
    {
        $groups = Group::all();
        $permission = null;

        // check request for passed filter values
        if ($request->input('filter_permission')) {
            $permission = $request->input('filter_permission');
            $groups = Group::getByPermission(ucfirst($permission))
                        ->orderBy('name', 'ASC')
                        ->get();
        };

        if ($request->input('filter_name')) {
            $name = $request->input('filter_name');
            $groups = Group::where('name', $name)->get();
        }

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
        $permissions = Permission::orderBy('name')->pluck('name', 'id')->all();
        $users = User::orderBy('name')->pluck('name', 'id')->all();

        return view('groups.create', compact('permissions', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(GroupRequest $request, Group $group)
    {
        $msg = '';

        $input = $request->all();

        $group = $group->create($input);

        $group->permissions()->attach($request->input('permission_list', []));
        $group->users()->attach($request->input('user_list', []));

        flash()->success('Success', 'Your group has been created');

        return redirect()->route('groups.index');
    }

    /**
     * Display the specified resource.
     *
     * @param Group $group
     * @return Response
     */
    public function show(Group $group)
    {
        return view('groups.show', compact('group'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Group $group
     * @return Response
     */
    public function edit(Group $group)
    {
        $this->middleware('auth');

        $permissions = Permission::orderBy('name')->pluck('name', 'id')->all();
        $users = User::orderBy('name')->pluck('name', 'id')->all();

        return view('groups.edit', compact('group', 'permissions', 'users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Group $group
     * @param Request $request
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
     * @param Group $group
     * @return Response
     * @throws \Exception
     */
    public function destroy(Group $group)
    {
        $group->delete();

        return redirect('groups');
    }
}
