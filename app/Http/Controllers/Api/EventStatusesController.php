<?php

namespace App\Http\Controllers\Api;

use App\Filters\EventStatusFilters;
use App\Models\Activity;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventStatusPatchRequest;
use App\Http\Requests\EventStatusRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\EventStatus;
use App\Services\SessionStore\ListParameterSessionStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class EventStatusesController extends Controller
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
    protected EventStatusFilters $filter;

    public function __construct(EventStatusFilters $filter)
    {
        $this->filter = $filter;

        $this->prefix = 'app.event-statuses.';

        $this->defaultLimit = 10;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['event_statuses.name' => 'asc'];

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
        $listParamSessionStore->setBaseIndex('internal_event_status');
        $listParamSessionStore->setKeyPrefix('internal_event_status_index');

        $listParamSessionStore->setIndexTab(action([EventStatusesController::class, 'index']));

        $baseQuery = EventStatus::query()->select('event_statuses.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['event_statuses.name' => 'asc']);

        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        $query = $listResultSet->getList();

        $eventStatuses = $query->paginate($listResultSet->getLimit());

        return response()->json($eventStatuses);
    }

    public function store(EventStatusRequest $request): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $input = $request->validated();

        $eventStatus = EventStatus::create($input);

        return response()->json($eventStatus);
    }

    public function show(EventStatus $eventStatus): JsonResponse
    {
        return response()->json($eventStatus);
    }

    /**
     * PUT: full replacement of the resource. Only `name` is fillable.
     */
    public function update(EventStatus $eventStatus, EventStatusRequest $request): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $eventStatus->fill($request->validated())->save();

        return response()->json($eventStatus);
    }

    /**
     * PATCH: partial update. Only fields present in the body are touched.
     */
    public function patch(EventStatus $eventStatus, EventStatusPatchRequest $request): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $input = $request->validated();
        $scalarInput = array_intersect_key($input, array_flip($eventStatus->getFillable()));
        if (!empty($scalarInput)) {
            $eventStatus->fill($scalarInput)->save();
        }

        return response()->json($eventStatus);
    }

    public function destroy(EventStatus $eventStatus): JsonResponse
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        if (!$eventStatus->deleteIfUnused()) {
            return response()->json(['message' => 'This event status is still in use and can\'t be deleted.'], 409);
        }

        Activity::log($eventStatus, $this->user, 3);

        return response()->json([], 204);
    }
}
