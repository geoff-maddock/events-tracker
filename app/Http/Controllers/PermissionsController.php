<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Permission;
use App\Group;

class PermissionsController extends Controller
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

        $permissions = Permission::orderBy($this->sortBy, $this->sortOrder)->paginate($this->rpp);

        return view('permissions.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder])
            ->with(compact('permissions'));
    }

    /**
     * Update the page list parameters from the request
     *
     */
    protected function updatePaging($request)
    {
        if (!empty($request->input('sort_by'))) {
            $this->sortBy = $request->input('sort_by');
        }

        if (!empty($request->input('sort_order'))) {
            $this->sortOrder = $request->input('sort_order');
        }

        if (!empty($request->input('rpp')) && is_numeric($request->input('rpp'))) {
            $this->rpp = $request->input('rpp');
        }
    }

    /**
     * Display a listing of permissions by group
     *
     * @return Response
     */
    public function indexGroups(Request $request, $group)
    {
        // updates sort, rpp from request
        $this->updatePaging($request);

        $permissions = Permission::getByGroup(ucfirst($group))
                    ->orderBy('name', 'ASC')
                    ->get();

        return view('permissions.index')
            ->with(['rpp' => $this->rpp, 'sortBy' => $this->sortBy, 'sortOrder' => $this->sortOrder])
            ->with(compact('permissions', 'group'));
    }

    /**
     * Filter the list of permissions
     *
     *
     * @return Response
     */
    public function filter(Request $request)
    {
        $name = null;

        $permissions = Permission::all();

        // check request for passed filter values
        if ($request->input('filter_group')) {
            $group = $request->input('filter_group');
            $permissions = Permission::getByGroup(ucfirst($group))
                        ->orderBy('name', 'ASC')
                        ->get();
        };

        if ($request->input('filter_name')) {
            $name = $request->input('filter_name');
            $permissions = Permission::where('name', $name)->get();
        }

        return view('permissions.index', compact('permissions', 'name'));
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

        return view('permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $groups = Group::orderBy('name', 'ASC')->pluck('name', 'id')->all();

        return view('permissions.create', compact('groups'));
    }

    /**
     * Store a newly created resource in storage.
     * @param PermissionRequest $request
     * @param Permission $permission
     *
     * @return Response
     */
    public function store(PermissionRequest $request, Permission $permission)
    {
        $msg = '';

        $input = $request->all();

        $permission = $permission->create($input);

        $permission->groups()->attach($request->input('group_list', []));

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
     * @param  Permission $permission
     * @return Response
     */
    public function edit(Permission $permission)
    {
        $this->middleware('auth');

        $groups = Group::orderBy('name')->pluck('name', 'id')->all();

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

        $permission->groups()->sync($request->input('group_list', []));

        return redirect('permissions');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Permission $permission
     * @return Response
     * @throws \Exception
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect('permissions');
    }
}
