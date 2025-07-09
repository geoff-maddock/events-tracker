<?php

namespace App\Http\Controllers\Api;

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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

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
        $this->filter = $filter;
        $this->prefix = 'app.roles.';
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

    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): JsonResponse {
        $listParamSessionStore->setBaseIndex('internal_role');
        $listParamSessionStore->setKeyPrefix('internal_role_index');
        $listParamSessionStore->setIndexTab(action([RolesController::class, 'index']));

        $baseQuery = Role::query()->select('roles.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['roles.name' => 'asc']);

        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        $query = $listResultSet->getList();
        $roles = $query->paginate($listResultSet->getLimit());

        return response()->json($roles);
    }

    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $keyPrefix = $request->get('key') ?? 'internal_role_index';
        $listParamSessionStore->setBaseIndex('internal_role');
        $listParamSessionStore->setKeyPrefix($keyPrefix);
        $listParamSessionStore->clearSort();

        return redirect()->route('roles.index');
    }

    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): Response {
        $keyPrefix = $request->get('key') ?? 'internal_role_index';
        $listParamSessionStore->setBaseIndex('internal_role');
        $listParamSessionStore->setKeyPrefix($keyPrefix);
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route('roles.index');
    }

    public function buildCriteria(Request $request): Builder
    {
        return Role::orderBy($this->sort, $this->sortDirection);
    }

    public function store(RoleRequest $request): JsonResponse
    {
        $input = $request->all();
        $role = Role::create($input);

        return response()->json($role, 201);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json($role);
    }

    public function update(Role $role, RoleRequest $request): JsonResponse
    {
        $role->fill($request->input())->save();

        return response()->json($role);
    }

    public function destroy(Role $role): JsonResponse
    {
        $name = $role->name;

        try {
            $role->delete();
        } catch (Exception $e) {
            Log::error(sprintf('Could not delete the role %s', $name));
        }

        Activity::log($role, $this->user, 3);

        return response()->json([], 204);
    }

    protected function getFilterOptions(): array
    {
        return [];
    }

    protected function getListControlOptions(): array
    {
        return [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['roles.name' => 'Name', 'roles.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }
}
