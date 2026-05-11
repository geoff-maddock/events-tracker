<?php

namespace App\Http\Controllers;

use App\Filters\ActivityFilters;
use App\Http\ResultBuilder\ListEntityResultBuilder;
use App\Models\Action;
use App\Models\Activity;
use App\Models\User;
use App\Services\SessionStore\ListParameterSessionStore;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    private const UNKNOWN_LABEL = 'Unknown';

    private const ALLOWED_GROUP_BY = ['day', 'week', 'month', 'year'];

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
            ->with(array_merge($graphData, ['filters' => $filters], $this->getGraphOptions()))
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

            fputcsv($output, ['Period', 'Activity Type', 'Count']);
            foreach ($rows as $row) {
                fputcsv($output, [$row->activity_date, $row->activity_type, $row->activity_count]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Grouping', $filters['group_by'] ?? 'day']);
            fputcsv($output, ['Included dates', $graphData['startDate']->toDateString().' to '.$graphData['endDate']->toDateString()]);
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function getGraphFilters(Request $request): array
    {
        $input = $request->only(['days', 'start_date', 'end_date', 'action_id', 'user_id', 'line_limit', 'object_table', 'group_by']);

        $validator = validator($input, [
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'action_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'line_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'object_table' => ['nullable', 'string', 'max:100'],
            'group_by' => ['nullable', 'in:' . implode(',', self::ALLOWED_GROUP_BY)],
        ]);

        $validated = $validator->fails() ? [] : $validator->validated();

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
            'group_by' => $validated['group_by'] ?? 'day',
        ];
    }

    protected function buildGraphData(array $filters): array
    {
        $startDate = Carbon::parse($filters['start_date'])->startOfDay();
        $endDate = Carbon::parse($filters['end_date'])->endOfDay();
        $lineLimit = max(1, (int) $filters['line_limit']);
        $groupBy = $filters['group_by'] ?? 'day';

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

        $topTypes = $this->resolveTopTypes(clone $baseQuery, $lineLimit);
        $dailyRows = $this->queryDailyRows(clone $baseQuery, $topTypes);
        $labels = $this->buildBucketLabels($startDate, $endDate, $groupBy);
        $rows = $this->bucketRows($dailyRows, $labels, $groupBy);
        $datasets = $this->buildDatasets($topTypes, $labels, $rows);
        $total = array_sum(array_map(fn($row) => (int) $row->activity_count, $rows->all()));

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'groupBy' => $groupBy,
            'total' => $total,
        ];
    }

    private function resolveTopTypes(\Illuminate\Database\Eloquent\Builder $query, int $limit): array
    {
        $expr = $this->getActivityTypeExpression();

        return $query
            ->selectRaw("{$expr} as activity_type, COUNT(*) as total_count")
            ->groupBy(DB::raw($expr))
            ->orderByDesc('total_count')
            ->limit($limit)
            ->pluck('activity_type')
            ->map(fn($t): string => (string) $t)
            ->all();
    }

    private function queryDailyRows(\Illuminate\Database\Eloquent\Builder $query, array $topTypes): \Illuminate\Support\Collection
    {
        if (empty($topTypes)) {
            return collect();
        }

        $expr = $this->getActivityTypeExpression();
        $dateExpr = 'DATE(activities.created_at)';

        return $query
            ->selectRaw("{$dateExpr} as activity_date, {$expr} as activity_type, COUNT(*) as activity_count")
            ->groupBy(DB::raw($dateExpr), DB::raw($expr))
            ->havingRaw("activity_type IN (" . implode(',', array_fill(0, count($topTypes), '?')) . ")", $topTypes)
            ->orderBy('activity_date', 'asc')
            ->orderBy('activity_type', 'asc')
            ->get()
            ->map(function (mixed $row): array {
                /** @var object{activity_date: string, activity_type: string, activity_count: int} $row */
                return [
                    'activity_date' => (string) $row->activity_date,
                    'activity_type' => (string) $row->activity_type,
                    'activity_count' => (int) $row->activity_count,
                ];
            });
    }

    private function bucketRows(\Illuminate\Support\Collection $dailyRows, array $labels, string $groupBy): \Illuminate\Support\Collection
    {
        $bucketed = [];
        foreach ($dailyRows as $row) {
            $bucket = $this->getBucketLabel(Carbon::parse($row['activity_date']), $groupBy);
            $bucketed[$bucket][$row['activity_type']] = ($bucketed[$bucket][$row['activity_type']] ?? 0) + $row['activity_count'];
        }

        $rows = collect();
        foreach ($labels as $bucketLabel) {
            foreach ($bucketed[$bucketLabel] ?? [] as $activityType => $count) {
                $rows->push((object) [
                    'activity_date' => $bucketLabel,
                    'activity_type' => $activityType,
                    'activity_count' => $count,
                ]);
            }
        }

        return $rows;
    }

    private function buildDatasets(array $topTypes, array $labels, \Illuminate\Support\Collection $rows): array
    {
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
            $datasets[] = ['label' => $type, 'data' => array_values($countsByDate)];
        }

        return $datasets;
    }

    protected function buildBucketLabels(Carbon $startDate, Carbon $endDate, string $groupBy): array
    {
        $labels = [];
        $cursor = $this->getBucketStart($startDate, $groupBy);
        $endBucket = $this->getBucketStart($endDate, $groupBy);

        while ($cursor->lte($endBucket)) {
            $labels[] = $this->getBucketLabel($cursor, $groupBy);
            $this->advanceBucketCursor($cursor, $groupBy);
        }

        return $labels;
    }

    protected function getBucketLabel(Carbon $date, string $groupBy): string
    {
        return match ($groupBy) {
            'week' => $date->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'),
            'month' => $date->copy()->startOfMonth()->format('Y-m'),
            'year' => $date->copy()->startOfYear()->format('Y'),
            default => $date->copy()->startOfDay()->format('Y-m-d'),
        };
    }

    protected function getBucketStart(Carbon $date, string $groupBy): Carbon
    {
        return match ($groupBy) {
            'week' => $date->copy()->startOfWeek(Carbon::MONDAY),
            'month' => $date->copy()->startOfMonth(),
            'year' => $date->copy()->startOfYear(),
            default => $date->copy()->startOfDay(),
        };
    }

    protected function advanceBucketCursor(Carbon $cursor, string $groupBy): void
    {
        match ($groupBy) {
            'week' => $cursor->addWeek(),
            'month' => $cursor->addMonth(),
            'year' => $cursor->addYear(),
            default => $cursor->addDay(),
        };
    }

    protected function getActivityTypeExpression(): string
    {
        $unknown = self::UNKNOWN_LABEL;

        if (DB::connection()->getDriverName() === 'sqlite') {
            return "COALESCE(actions.name, '{$unknown}') || ' ' || COALESCE(activities.object_table, '{$unknown}')";
        }

        return "CONCAT(COALESCE(actions.name, '{$unknown}'), ' ', COALESCE(activities.object_table, '{$unknown}'))";
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

    protected function getGraphOptions(): array
    {
        return [
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
            'groupByOptions' => array_combine(self::ALLOWED_GROUP_BY, array_map('ucfirst', self::ALLOWED_GROUP_BY)),
        ];
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
