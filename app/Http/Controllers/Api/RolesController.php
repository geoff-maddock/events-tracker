<?php

namespace App\Http\Controllers\Api;

use App\Filters\RoleFilters;
use App\Models\Activity;
use App\Http\Controllers\Controller;
use App\Http\Requests\RolePatchRequest;
use App\Http\Requests\RoleRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;
use App\Services\SessionStore\ListParameterSessionStore;
use Exception;
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

    public function store(RoleRequest $request): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $input = $request->validated();
        $role = Role::create($input);

        return response()->json($role, 201);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json($role);
    }

    /**
     * PUT: full replacement of the resource. Optional `short` is reset to
     * null when omitted from the body.
     */
    public function update(Role $role, RoleRequest $request): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $input = $request->validated();

        if (!array_key_exists('short', $input)) {
            $input['short'] = null;
        }

        $role->fill($input)->save();

        return response()->json($role);
    }

    /**
     * PATCH: partial update. Only fields present in the body are touched.
     */
    public function patch(Role $role, RolePatchRequest $request): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $input = $request->validated();
        $scalarInput = array_intersect_key($input, array_flip($role->getFillable()));
        if (!empty($scalarInput)) {
            $role->fill($scalarInput)->save();
        }

        return response()->json($role);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $name = $role->name;

        try {
            $role->delete();
        } catch (Exception $e) {
            Log::error(sprintf('Could not delete the role %s', $name));
        }

        Activity::log($role, $this->user, 3);

        return response()->json([], 204);
    }
}
