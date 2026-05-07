<?php

namespace App\Http\Controllers;

use App\Filters\ActivityFilters;
use App\Http\Requests\SeriesRequest;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use App\Services\SessionStore\ListParameterSessionStore;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    private const UNKNOWN_LABEL = 'Unknown';

    protected string $prefix;

    protected int $defaultLimit;

    protected string $defaultSort;

    protected string $defaultSortDirection;

    protected int $limit;

    protected string $sort;

    protected string $sortDirection;

    protected array $defaultSortCriteria;

    protected bool $hasFilter = false;

    protected array $filters;

    // this is the class specifying the filters methods for each field
    protected ActivityFilters $filter;

    public function __construct(ActivityFilters $filter)
    {
        $this->middleware('auth', ['only' => ['create', 'edit', 'store', 'update']]);
        $this->middleware(['auth', 'can:admin'], ['only' => ['graph', 'exportGraph']]);
        $this->filter = $filter;

        // prefix for session storage
        $this->prefix = 'app.activities.';

        // default list variables
        $this->defaultLimit = 100;
        $this->defaultSort = 'name';
        $this->defaultSortDirection = 'asc';
        $this->defaultSortCriteria = ['object_name' => 'desc'];

        $this->limit = 100;
        $this->sort = 'object_name';
        $this->sortDirection = 'desc';

        parent::__construct();
    }

    public function filter(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_activity');
        $listParamSessionStore->setKeyPrefix('internal_activity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ActivityController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Activity::query()->select('activities.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['activities.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the activities
        $activities = $query
            ->with('user', 'action')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('activities.index-tw')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('activities'))
            ->render();
    }

    public function index(
        Request $request,
        ListParameterSessionStore $listParamSessionStore,
        ListEntityResultBuilder $listEntityResultBuilder
    ): string {
        // initialized listParamSessionStore with baseindex key
        $listParamSessionStore->setBaseIndex('internal_activity');
        $listParamSessionStore->setKeyPrefix('internal_activity_index');

        // set the index tab in the session
        $listParamSessionStore->setIndexTab(action([ActivityController::class, 'index']));

        // create the base query including any required joins; needs select to make sure only event entities are returned
        $baseQuery = Activity::query()->select('activities.*');

        $listEntityResultBuilder
            ->setFilter($this->filter)
            ->setQueryBuilder($baseQuery)
            ->setDefaultSort(['activities.created_at' => 'desc']);

        // get the result set from the builder
        $listResultSet = $listEntityResultBuilder->listResultSetFactory();

        // get the query builder
        $query = $listResultSet->getList();

        // get the activities
        $activities = $query
            ->with('user', 'action')
            ->paginate($listResultSet->getLimit());

        // saves the updated session
        $listParamSessionStore->save();

        $this->hasFilter = $listResultSet->getFilters() != $listResultSet->getDefaultFilters() || $listResultSet->getIsEmptyFilter();

        return view('activities.index-tw')
            ->with(array_merge(
                [
                    'limit' => $listResultSet->getLimit(),
                    'sort' => $listResultSet->getSort(),
                    'direction' => $listResultSet->getSortDirection(),
                    'hasFilter' => $this->hasFilter,
                    'filters' => $listResultSet->getFilters(),
                ],
                $this->getFilterOptions(),
                $this->getListControlOptions()
            ))
            ->with(compact('activities'))
            ->render();
    }

    public function graph(Request $request): string
    {
        $filters = $this->getGraphFilters($request);
        $graphData = $this->buildGraphData($filters);

        return view('activities.graph-tw')
            ->with(array_merge($graphData, [
                'filters' => $filters,
                'actionOptions' => ['' => 'All actions'] + Action::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
                'tableOptions' => ['' => 'All tables'] + Activity::query()
                    ->whereNotNull('object_table')
                    ->where('object_table', '!=', '')
                    ->orderBy('object_table', 'asc')
                    ->distinct()
                    ->pluck('object_table', 'object_table')
                    ->all(),
                'userOptions' => ['' => 'All users'] + User::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
                'daysOptions' => [7 => 'Last 7 days', 14 => 'Last 14 days', 30 => 'Last 30 days', 90 => 'Last 90 days'],
                'lineLimitOptions' => [5 => 'Top 5', 10 => 'Top 10', 20 => 'Top 20', 50 => 'Top 50'],
            ]))
            ->render();
    }

    public function exportGraph(Request $request): StreamedResponse
    {
        $filters = $this->getGraphFilters($request);
        $graphData = $this->buildGraphData($filters);
        $rows = $graphData['rows'];
        $filename = 'activity-graph-'.Carbon::now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows, $graphData, $filters): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                Log::error('Failed to open output stream for activity graph export', [
                    'filters' => $filters,
                ]);
                return;
            }

            fputcsv($output, ['Date', 'Activity Type', 'Count']);
            foreach ($rows as $row) {
                fputcsv($output, [$row->activity_date, $row->activity_type, $row->activity_count]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Included dates', $graphData['startDate']->toDateString().' to '.$graphData['endDate']->toDateString()]);
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function getGraphFilters(Request $request): array
    {
        $validated = Validator::make($request->all(), [
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'action_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'line_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'object_table' => ['nullable', 'string', 'max:100'],
        ])->validate();

        $days = max(1, min(365, (int) ($validated['days'] ?? 7)));
        $startDateInput = $validated['start_date'] ?? null;
        $endDateInput = $validated['end_date'] ?? null;
        $today = Carbon::today();

        if ($startDateInput || $endDateInput) {
            $startDate = $startDateInput ? Carbon::parse($startDateInput)->startOfDay() : $today->copy()->subDays($days - 1)->startOfDay();
            $endDate = $endDateInput ? Carbon::parse($endDateInput)->endOfDay() : $today->copy()->endOfDay();
        } else {
            $endDate = $today->copy()->endOfDay();
            $startDate = $today->copy()->subDays($days - 1)->startOfDay();
        }

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [
            'days' => $days,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'action_id' => isset($validated['action_id']) ? (int) $validated['action_id'] : null,
            'object_table' => isset($validated['object_table']) && $validated['object_table'] !== '' ? (string) $validated['object_table'] : null,
            'user_id' => isset($validated['user_id']) ? (int) $validated['user_id'] : null,
            'line_limit' => max(1, min(100, (int) ($validated['line_limit'] ?? 10))),
        ];
    }

    protected function buildGraphData(array $filters): array
    {
        $startDate = Carbon::parse($filters['start_date'])->startOfDay();
        $endDate = Carbon::parse($filters['end_date'])->endOfDay();
        $days = $startDate->diffInDays($endDate) + 1;
        $lineLimit = max(1, (int) $filters['line_limit']);

        $baseQuery = Activity::query()
            ->leftJoin('actions', 'activities.action_id', '=', 'actions.id')
            ->whereBetween('activities.created_at', [$startDate, $endDate]);

        if (!empty($filters['action_id'])) {
            $baseQuery->where('activities.action_id', $filters['action_id']);
        }

        if (!empty($filters['object_table'])) {
            $baseQuery->where('activities.object_table', $filters['object_table']);
        }

        if (!empty($filters['user_id'])) {
            $baseQuery->where('activities.user_id', $filters['user_id']);
        }

        $activityTypeExpression = $this->getActivityTypeExpression();
        $dateExpression = 'DATE(activities.created_at)';

        $rows = (clone $baseQuery)
            ->selectRaw("{$dateExpression} as activity_date, {$activityTypeExpression} as activity_type, COUNT(*) as activity_count")
            ->groupBy(DB::raw($dateExpression), DB::raw($activityTypeExpression))
            ->orderBy('activity_date', 'asc')
            ->orderBy('activity_type', 'asc')
            ->get();

        $topTypes = (clone $baseQuery)
            ->selectRaw("{$activityTypeExpression} as activity_type, COUNT(*) as total_count")
            ->groupBy(DB::raw($activityTypeExpression))
            ->orderByDesc('total_count')
            ->limit($lineLimit)
            ->pluck('activity_type')
            ->all();

        $rows = $rows->filter(function ($row) use ($topTypes) {
            return in_array($row->activity_type, $topTypes, true);
        })->values();

        $labels = [];
        $cursor = $startDate->copy()->startOfDay();
        for ($i = 0; $i < $days; $i++) {
            $labels[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        $datasetsMap = [];
        foreach ($topTypes as $type) {
            $datasetsMap[$type] = array_fill_keys($labels, 0);
        }

        foreach ($rows as $row) {
            if (isset($datasetsMap[$row->activity_type][$row->activity_date])) {
                $datasetsMap[$row->activity_type][$row->activity_date] = (int) $row->activity_count;
            }
        }

        $datasets = [];
        foreach ($datasetsMap as $type => $countsByDate) {
            $datasets[] = [
                'label' => $type,
                'data' => array_values($countsByDate),
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    protected function getActivityTypeExpression(): string
    {
        $unknown = self::UNKNOWN_LABEL;

        if (DB::connection()->getDriverName() === 'sqlite') {
            return "COALESCE(actions.name, '{$unknown}') || ' ' || COALESCE(activities.object_table, '{$unknown}')";
        }

        return "CONCAT(COALESCE(actions.name, '{$unknown}'), ' ', COALESCE(activities.object_table, '{$unknown}'))";
    }

    protected function unauthorized(SeriesRequest $request): Response | RedirectResponse
    {
        if ($request->ajax()) {
            return response(['message' => 'No way.'], 403);
        }

        Session::flash('flash_message', 'Not authorized');

        return redirect('/');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $activity->delete();

        return redirect('activity');
    }

    /**
     * Get the default filters array.
     */
    public function getDefaultFilters(): array
    {
        return [];
    }

    /**
     * Reset the rpp, sort, order.
     *
     * @throws \Throwable
     */
    public function rppReset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set the rpp, sort, direction only to default values
        $keyPrefix = $request->get('key') ?? 'internal_activity_index';
        $listParamSessionStore->setBaseIndex('internal_activity');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear sorting from session
        $listParamSessionStore->clearSort();

        return redirect()->route('activities.index');
    }

    /**
     * Reset the filtering of entities.
     */
    public function reset(
        Request $request,
        ListParameterSessionStore $listParamSessionStore
    ): RedirectResponse {
        // set filters and list controls to default values
        $keyPrefix = $request->get('key') ?? 'internal_activity_index';
        $listParamSessionStore->setBaseIndex('internal_activity');
        $listParamSessionStore->setKeyPrefix($keyPrefix);

        // clear
        $listParamSessionStore->clearFilter();
        $listParamSessionStore->clearSort();

        return redirect()->route($request->get('redirect') ?? 'activities.index');
    }

    protected function getListControlOptions(): array
    {
        return  [
            'limitOptions' => [5 => 5, 10 => 10, 25 => 25, 100 => 100, 1000 => 1000],
            'sortOptions' => ['activities.object_name' => 'Name', 'activities.object_table' => 'Table', 'activities.created_at' => 'Created At'],
            'directionOptions' => ['asc' => 'asc', 'desc' => 'desc'],
        ];
    }

    protected function getFilterOptions(): array
    {
        return  [
            'actionOptions' => ['' => '&nbsp;'] + Action::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
            'userOptions' => ['' => ''] + User::orderBy('name', 'ASC')->pluck('name', 'name')->all(),
        ];
    }
}
