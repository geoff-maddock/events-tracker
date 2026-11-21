<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
use App\Filters\RoleFilters;
use App\Models\Activity;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RolesController extends Controller
{
    use BuildsListPages;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected array $defaultSortCriteria;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $filters;

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

        $listResultSet = $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_role',
            'internal_role_index',
            action([RolesController::class, 'index']),
            $this->filter,
            Role::query()->select('roles.*'),
            ['roles.name' => 'asc'],
        );

        $roles = $listResultSet->getList()->paginate($listResultSet->getLimit());

        return view('roles.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet))
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
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
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

        $this->clearList($listParamSessionStore, 'internal_role', $request->get('key') ?? 'internal_role_index', filters: false);

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

        $this->clearList($listParamSessionStore, 'internal_role', $request->get('key') ?? 'internal_role_index', filters: true);

        return redirect()->route('roles.index');
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


        $role->create($request->validated());

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

        $role->fill($request->validated())->save();

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

        if (!$role->deleteIfUnused()) {
            flash()->error('Not deleted', sprintf('The role %s is still in use and can\'t be deleted.', $name));

            return redirect('roles');
        }

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
