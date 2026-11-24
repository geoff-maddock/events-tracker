<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BuildsListPages;
use App\Filters\PermissionFilters;
use App\Http\Requests\PermissionRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Group;
use App\Models\Permission;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionsController extends Controller
{
    use BuildsListPages;

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

    // this is the class specifying the filters methods for each field
    protected PermissionFilters $filter;

    public function __construct(PermissionFilters $filter)
    {
        // group and permission changes grant privileges, so every write is admin-only
        // listing them shows who the admins are, so reading is admin-only too (#2233)
        $this->middleware(['auth', 'can:admin']);
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
        $listResultSet = $this->startList(
            $listParamSessionStore,
            $listEntityResultBuilder,
            'internal_permission',
            'internal_permission_index',
            action([PermissionsController::class, 'index']),
            $this->filter,
            Permission::query()->select('permissions.*'),
            ['permissions.created_at' => 'desc'],
        );

        $permissions = $listResultSet->getList()->paginate($listResultSet->getLimit());

        return view('permissions.index-tw')
            ->with($this->finishList($listParamSessionStore, $listResultSet))
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
        return $this->index($request, $listParamSessionStore, $listEntityResultBuilder);
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['permissions.name' => 'Name', 'permissions.created_at' => 'Created At', 'permissions.label' => 'Label', 'permissions.level' => 'Level'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFilterOptions(): array
    {
        return [
        ];
    }

    /**
     * Reset the limit, sort, direction.
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_permission', $request->get('key') ?? 'internal_permission_index', filters: false);

        return redirect()->route('permissions.index');
    }

    /**
     * Reset the filtering of entities.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $this->clearList($listParamSessionStore, 'internal_permission', $request->get('key') ?? 'internal_permission_index', filters: true);

        return redirect()->route($this->resolveRedirectRoute($request, 'permissions.index'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('permissions.create-tw')
            ->with($this->getFormOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PermissionRequest $request, Permission $permission): RedirectResponse
    {
        $msg = '';

        $permission = $permission->create($this->validatedFields($request));

        // a repeated id would be the same link twice
        $permission->groups()->attach(array_unique((array) $request->input('group_list', [])));

        flash()->success('Success', 'Your permission has been created');

        return redirect()->route('permissions.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission): View
    {
        return view('permissions.show-tw', compact('permission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission): View
    {
        $groups = Group::orderBy('name')->pluck('name', 'id')->all();

        return view('permissions.edit-tw', compact('permission'))
        ->with($this->getFormOptions());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Permission $permission, PermissionRequest $request): RedirectResponse
    {
        $msg = '';

        $permission->fill($this->validatedFields($request))->save();

        $permission->groups()->sync($request->input('group_list', []));

        return redirect('permissions');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(Permission $permission): RedirectResponse
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

    /**
     * The validated fields, with a blank description stored as '' — the column is
     * NOT NULL without a default, so a null description was a 500.
     *
     * @return array<string, mixed>
     */
    protected function validatedFields(PermissionRequest $request): array
    {
        $fields = $request->validated();
        $fields['description'] = $fields['description'] ?? '';

        return $fields;
    }
}
