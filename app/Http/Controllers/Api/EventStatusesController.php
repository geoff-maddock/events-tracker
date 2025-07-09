<?php

namespace App\Http\Controllers\Api;

use App\Filters\EventStatusFilters;
use App\Models\Activity;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventStatusRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\EventStatus;
use App\Services\SessionStore\ListParameterSessionStore;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

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

    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        $keyPrefix = $request->get('key') ?? 'internal_event_status_index';
        $listParamSessionStore->setBaseIndex('internal_event_status');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        $listParamSessionStore->clearSort();

        return redirect()->route('event-statuses.index');
    }

    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): Response {
        $keyPrefix = $request->get('key') ?? 'internal_event_status_index';
        $listParamSessionStore->setBaseIndex('internal_event_status');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route('event-statuses.index');
    }

    public function buildCriteria(Request $request): Builder
    {
        $query = EventStatus::orderBy($this->sort, $this->sortDirection);

        return $query;
    }

    public function store(EventStatusRequest $request): JsonResponse
    {
        $input = $request->all();

        $eventStatus = EventStatus::create($input);

        return response()->json($eventStatus);
    }

    public function show(EventStatus $eventStatus): JsonResponse
    {
        return response()->json($eventStatus);
    }

    public function update(EventStatus $eventStatus, EventStatusRequest $request): JsonResponse
    {
        $eventStatus->fill($request->input())->save();

        return response()->json($eventStatus);
    }

    public function destroy(EventStatus $eventStatus): JsonResponse
    {
        $name = $eventStatus->name;

        try {
            $eventStatus->delete();
        } catch (Exception $e) {
            Log::error(sprintf('Could not delete the event status %s', $name));
        }

        Activity::log($eventStatus, $this->user, 3);

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
            'sortOptions' => ['event_statuses.name' => 'Name', 'event_statuses.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }
}
