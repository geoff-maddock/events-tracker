<?php

namespace App\Http\Controllers;

use App\Filters\PermissionFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Permission;
use App\Models\Group;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;

class PermissionsController extends Controller
{
    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    // array of sort criteria to be applied in order
    protected array $sortCriteria;

    protected array $filters;

    protected bool $hasFilter;

    // this is the class specifying the filters methods for each field
    protected PermissionFilters $filter;

    public function __construct(PermissionFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.permissions.';

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';

        // set list variables
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;
        $this->limit = $this->defaultLimit;
        $this->sortCriteria = ['name', 'desc'];

        $this->hasFilter = false;

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_permission');
        $listParamSessionStore->setKeyPrefix('internal_permission_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([PermissionsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Permission::query()->select('permissions.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['permissions.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $permissions = $query->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('permissions.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters()
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('permissions'))
            ->render();
    }

    /**
     * Filter a listing of the resource.
     */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_permission');
        $listParamSessionStore->setKeyPrefix('internal_permission_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([PermissionsController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Permission::query()->select('permissions.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['permissions.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the events
        $permissions = $query->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('permissions.index')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters()
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('permissions'))
            ->render();
    }

    protected function getListControlOptions(): array
    {
        return  [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['permissions.name' => 'Name', 'permissions.created_at' => 'Created At', 'permissions.label' => 'Label', 'permissions.level' => 'Level'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc']
        ];
    }

    protected function getFilterOptions(): array
    {
        return  [
        ];
    }

    /**
     * Reset the limit, sort, direction
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set the limit, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_permission_index';
        $listParamSessionStore->setBaseIndex('internal_permission');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('permissions.index');
    }

    /**
     * Reset the filtering of entities.
     *
     * @return Response
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ) {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_permission_index';
        $listParamSessionStore->setBaseIndex('internal_permission');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'permissions.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('permissions.create')
            ->with($this->getFormOptions());
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
     * @param  Permission $permission
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

        return view('permissions.edit', compact('permission'))
        ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Permission $permission
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

    protected function getFormOptions(): array
    {
        return [
            'groupOptions' => Permission::orderBy('name')->pluck('name', 'id')->all(),
        ];
    }
}
