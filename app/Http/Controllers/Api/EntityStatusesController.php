<?php

namespace App\Http\Controllers\Api;

use App\Filters\EntityStatusFilters;
use App\Models\Activity;
use App\Http\Controllers\Controller;
use App\Http\Requests\EntityStatusRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\EntityStatus;
use App\Services\SessionStore\ListParameterSessionStore;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse as HttpFoundationJsonResponse;

class EntityStatusesController extends Controller
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

    protected EntityStatusFilters $filter;

    public function __construct(EntityStatusFilters $filter)
    {
        // $this->middleware('auth', ['except' => ['index', 'show']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.entity-statuses.';

        // default list variables
        $this->defaultLimit = 10;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['entityStatuses.name' => 'asc'];

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
        $listParamSessionStore->setIndexTab(action([EntityStatusesController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = EntityStatus::query()->select('entity_statuses.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['entity_statuses.name' => 'asc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the entities
        $entityStatuses = $query
            ->paginate($listResultSet->getLimit());

        return response()->json($entityStatuses);
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
        // set the rpp, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_entity_type_index';
        $listParamSessionStore->setBaseIndex('internal_entity_type');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearSort();

        return redirect()->route('entity-types.index');
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
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_entity_type_index';
        $listParamSessionStore->setBaseIndex('internal_entity_type');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route('entity-types.index');
    }

    /**
     * Builds the criteria from the session.
     */
    public function buildCriteria(Request $request): Builder
    {
        // base criteria
        $query = EntityStatus::orderBy($this->sort, $this->sortDirection);

        return $query;
    }

    /**
     * Store a newly created resource in storage.
     *
     */
    public function store(EntityStatusRequest $request, EntityStatus $entityStatus): JsonResponse
    {
        $input = $request->all();

        $entityStatus = EntityStatus::create($input);

        return response()->json($entityStatus);
    }

    /**
     * Display the specified resource.
     */
    public function show(EntityStatus $entityStatus): JsonResponse
    {
        return response()->json($entityStatus);
    }

    /**
     * Update the specified resource in storage.
     *
     */
    public function update(EntityStatus $entityStatus, EntityStatusRequest $request): JsonResponse
    {
        $entityStatus->fill($request->input())->save();

        return response()->json($entityStatus);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EntityStatus $entityStatus): JsonResponse
    {
        $name = $entityStatus->name;

        try {
            $entityStatus->delete();
        } catch (Exception $e) {
            Log::error(sprintf('Could not delete the entity type %s', $name));
        };

        // add to activity log
        Activity::log($entityStatus, $this->user, 3);

        return response()->json([], 204);
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
            'sortOptions' => ['entity_statuses.name' => 'Name', 'entity_statuses.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc']
        ];
    }
}
