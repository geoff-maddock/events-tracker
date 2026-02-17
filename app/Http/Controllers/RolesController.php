<?php

namespace App\Http\Controllers;

use App\Filters\RoleFilters;
use App\Models\Activity;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;
use App\Services\SessionStore\ListParameterSessionStore;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RolesController extends Controller
{
    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

    protected bool $hasFilter;

    protected string $prefix;

    protected RoleFilters $filter;

    public function __construct(RoleFilters $filter)
    {
        $this->middleware('auth');
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.roles.';

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['roles.name' => 'asc'];

        $this->limit = $this->defaultLimit;
        $this->sort = $this->defaultSort;
        $this->sortDirection = $this->defaultSortDirection;

        $this->hasFilter = false;
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     *
     * @throws \Throwable
     */
    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // Check admin permission
        if (!$request->user()?->hasGroup('admin')) {
            abort(403, 'Unauthorized access');
        }

        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_role');
        $listParamSessionStore->setKeyPrefix('internal_role_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([RolesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Role::query()->select('roles.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['roles.name' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the entities
        $roles = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('roles.index-tw')
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
            ->with(compact('roles'))
            ->render();
    }

    /**
    * Filter list of roles
    *
    *
    * @throws \Throwable
    */
    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // Check admin permission
        if (!$request->user()?->hasGroup('admin')) {
            abort(403, 'Unauthorized access');
        }

        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_role');
        $listParamSessionStore->setKeyPrefix('internal_role_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([RolesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Role::query()->select('roles.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['roles.name' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the entities
        $roles = $query
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('roles.index-tw')
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
            ->with(compact('roles'))
            ->render();
    }

    /**
     * Reset the rpp, sort, order
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // Check admin permission
        if (!$request->user()?->hasGroup('admin')) {
            abort(403, 'Unauthorized access');
        }

        // set the rpp, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_role_index';
        $listParamSessionStore->setBaseIndex('internal_role');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('roles.index');
    }

    /**
     * Reset the filtering of entities.
     *
     * @return Response
     *
     * @throws \Throwable
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): Response {
        // Check admin permission
        if (!$request->user()?->hasGroup('admin')) {
            abort(403, 'Unauthorized access');
        }

        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_role_index';
        $listParamSessionStore->setBaseIndex('internal_role');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route('roles.index');
    }

    /**
     * Builds the criteria from the session.
     */
    public function buildCriteria(Request $request): Builder
    {
        // base criteria
        $query = Role::orderBy($this->sort, $this->sortDirection);

        return $query;
    }

    /**
     * Show the form for creating a new resource.
     *
     */
    public function create(Request $request): View
    {
        // Check admin permission
        if (!$request->user()?->hasGroup('admin')) {
            abort(403, 'Unauthorized access');
        }

        return view('roles.create-tw');
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(RoleRequest $request, Role $role): RedirectResponse
    {
        // Check admin permission
        if (!$request->user()?->hasGroup('admin')) {
            abort(403, 'Unauthorized access');
        }

        $input = $request->all();

        $role->create($input);

        flash()->success('Success', 'Your role has been created!');

        return redirect()->route('roles.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Role $role): View
    {
        // Check admin permission
        if (!$request->user()?->hasGroup('admin')) {
            abort(403, 'Unauthorized access');
        }

        return view('roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Role $role): View
    {
        // Check admin permission
        if (!$request->user()?->hasGroup('admin')) {
            abort(403, 'Unauthorized access');
        }

        return view('roles.edit-tw', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(Role $role, RoleRequest $request): RedirectResponse
    {
        // Check admin permission
        if (!$request->user()?->hasGroup('admin')) {
            abort(403, 'Unauthorized access');
        }

        $role->fill($request->input())->save();

        flash()->success('Success', 'Your role has been updated!');

        return redirect('roles');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Role $role): RedirectResponse
    {
        // Check admin permission
        if (!$request->user()?->hasGroup('admin')) {
            abort(403, 'Unauthorized access');
        }

        $name = $role->name;

        try {
            $role->delete();
        } catch (Exception $e) {
            Log::error(sprintf('Could not delete the role %s', $name));
            flash()->error('Error', sprintf('Could not delete role %s', $name));
            return redirect('roles');
        };

        // add to activity log
        Activity::log($role, $this->user, 3);

        flash()->success('Success', sprintf('Your role %s has been deleted!', $name));

        return redirect('roles');
    }

    protected function getFilterOptions(): array
    {
        return  [
        ];
    }

    protected function getListControlOptions(): array
    {
        return  [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['roles.name' => 'Name', 'roles.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc']
        ];
    }
}
