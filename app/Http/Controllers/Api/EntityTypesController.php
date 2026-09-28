<?php

namespace App\Http\Controllers\Api;

use App\Filters\EntityTypeFilters;
use App\Models\Activity;
use App\Http\Controllers\Controller;
use App\Http\Requests\EntityTypePatchRequest;
use App\Http\Requests\EntityTypeRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\EntityType;
use App\Services\SessionStore\ListParameterSessionStore;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class EntityTypesController extends Controller
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

    protected EntityTypeFilters $filter;

    public function __construct(EntityTypeFilters $filter)
    {
        // TODO Handle API auth
        // $this->middleware('auth', ['except' => ['index', 'show']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.entity-types.';

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['entityTypes.name' => 'asc'];

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
    ): JsonResponse {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_entity_type');
        $listParamSessionStore->setKeyPrefix('internal_entity_type_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([EntityTypesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = EntityType::query()->select('entity_types.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entity_types.name' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the entities
        $entityTypes = $query
            ->paginate($listResultSet->getLimit());

        return response()->json($entityTypes);
    }


    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(EntityTypeRequest $request): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $input = $request->validated();

        $entityType = EntityType::create($input);

        return response()->json($entityType);
    }

    /**
     * Display the specified resource.
     */
    public function show(EntityType $entityType): JsonResponse
    {
        return response()->json($entityType);
    }


    /**
     * PUT: full replacement of the resource. All fillable fields are
     * required by EntityTypeRequest, so PUT and PATCH differ only in
     * validation strictness.
     */
    public function update(EntityType $entityType, EntityTypeRequest $request): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $entityType->fill($request->validated())->save();

        return response()->json($entityType);
    }

    /**
     * PATCH: partial update. Only fields present in the body are touched.
     */
    public function patch(EntityType $entityType, EntityTypePatchRequest $request): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $input = $request->validated();
        $scalarInput = array_intersect_key($input, array_flip($entityType->getFillable()));
        if (!empty($scalarInput)) {
            $entityType->fill($scalarInput)->save();
        }

        return response()->json($entityType);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EntityType $entityType): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $name = $entityType->name;

        try {
            $entityType->delete();
        } catch (Exception $e) {
            Log::error(sprintf('Could not delete the entity type %s', $name));
        };

        // add to activity log
        Activity::log($entityType, $this->user, 3);

        return response()->json([], 204);
    }
}
