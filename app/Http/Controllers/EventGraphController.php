<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BucketsGraphData;
use App\Models\EntityType;
use App\Models\Event;
use App\Models\EventType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventGraphController extends Controller
{
    use BucketsGraphData;

    protected const ALLOWED_SERIES_BY = ['total', 'event_type', 'venue', 'promoter', 'series', 'tag', 'entity'];

    protected const ALLOWED_DATE_FIELDS = ['start_at', 'created_at'];

    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
        parent::__construct();
    }

    public function graph(Request $request): string
    {
        $filters = $this->getGraphFilters($request);
        $graphData = $this->buildGraphData($filters);

        return view('events.graph-tw')
            ->with(array_merge($graphData, ['filters' => $filters], $this->getGraphOptions()))
            ->render();
    }

    public function exportGraph(Request $request): StreamedResponse
    {
        $filters = $this->getGraphFilters($request);
        $graphData = $this->buildGraphData($filters);
        $rows = $graphData['rows'];
        $filename = 'event-graph-'.Carbon::now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows, $graphData, $filters): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                Log::error('Failed to open output stream for event graph export', [
                    'filters' => $filters,
                ]);
                return;
            }

            fputcsv($output, ['Period', 'Series', 'Count']);
            foreach ($rows as $row) {
                fputcsv($output, [$row->period, $row->series_label, $row->event_count]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Breakdown', $filters['series_by']]);
            fputcsv($output, ['Date basis', $filters['date_field']]);
            fputcsv($output, ['Grouping', $filters['group_by']]);
            fputcsv($output, ['Included dates', $graphData['startDate']->toDateString().' to '.$graphData['endDate']->toDateString()]);
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function getGraphFilters(Request $request): array
    {
        $input = $request->only([
            'days', 'start_date', 'end_date', 'group_by', 'series_by', 'date_field',
            'event_type_id', 'entity_type_id', 'tag', 'line_limit',
        ]);

        $validator = validator($input, [
            'days' => ['nullable', 'integer', 'min:1', 'max:1095'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'group_by' => ['nullable', 'in:'.implode(',', self::ALLOWED_GROUP_BY)],
            'series_by' => ['nullable', 'in:'.implode(',', self::ALLOWED_SERIES_BY)],
            'date_field' => ['nullable', 'in:'.implode(',', self::ALLOWED_DATE_FIELDS)],
            'event_type_id' => ['nullable', 'integer', 'min:1'],
            'entity_type_id' => ['nullable', 'integer', 'min:1'],
            'tag' => ['nullable', 'string', 'max:100'],
            'line_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $validated = $validator->fails() ? [] : $validator->validated();

        $days = max(1, min(1095, (int) ($validated['days'] ?? 90)));
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
            'group_by' => $validated['group_by'] ?? 'week',
            'series_by' => $validated['series_by'] ?? 'event_type',
            'date_field' => $validated['date_field'] ?? 'start_at',
            'event_type_id' => isset($validated['event_type_id']) ? (int) $validated['event_type_id'] : null,
            'entity_type_id' => isset($validated['entity_type_id']) ? (int) $validated['entity_type_id'] : null,
            'tag' => isset($validated['tag']) && trim((string) $validated['tag']) !== '' ? trim((string) $validated['tag']) : null,
            'line_limit' => max(1, min(100, (int) ($validated['line_limit'] ?? 10))),
        ];
    }

    protected function buildGraphData(array $filters): array
    {
        $startDate = Carbon::parse($filters['start_date'])->startOfDay();
        $endDate = Carbon::parse($filters['end_date'])->endOfDay();
        $lineLimit = max(1, (int) $filters['line_limit']);
        $groupBy = $filters['group_by'];
        $seriesBy = $filters['series_by'];
        $dateField = in_array($filters['date_field'], self::ALLOWED_DATE_FIELDS, true) ? $filters['date_field'] : 'start_at';

        $baseQuery = Event::query()
            ->whereBetween('events.'.$dateField, [$startDate, $endDate]);

        if (!empty($filters['event_type_id'])) {
            $baseQuery->where('events.event_type_id', $filters['event_type_id']);
        }
        if (!empty($filters['tag'])) {
            $tag = $filters['tag'];
            $baseQuery->whereHas('tags', function ($query) use ($tag): void {
                $query->where('tags.name', 'like', '%'.$tag.'%')
                    ->orWhere('tags.slug', 'like', '%'.$tag.'%');
            });
        }
        // when the breakdown is not by entity, the entity type acts as an event filter instead
        if (!empty($filters['entity_type_id']) && $seriesBy !== 'entity') {
            $baseQuery->whereHas('entities', function ($query) use ($filters): void {
                $query->where('entities.entity_type_id', $filters['entity_type_id']);
            });
        }

        $seriesExpr = $this->applySeriesDimension($baseQuery, $seriesBy, $filters);

        $topSeries = $this->resolveTopSeries(clone $baseQuery, $seriesExpr, $lineLimit);
        $dailyRows = $this->queryDailyRows(clone $baseQuery, $seriesExpr, $dateField, $topSeries);
        $labels = $this->buildBucketLabels($startDate, $endDate, $groupBy);
        $rows = $this->bucketRows($dailyRows, $labels, $groupBy);
        $datasets = $this->buildDatasets($topSeries, $labels, $rows);
        $total = array_sum(array_map(fn ($row) => (int) $row->event_count, $rows->all()));

        return [
            'labels' => $labels,
            'datasets' => $datasets,
            'rows' => $rows,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'groupBy' => $groupBy,
            'seriesBy' => $seriesBy,
            'total' => $total,
        ];
    }

    /**
     * Add the joins needed for the requested breakdown and return the SQL expression
     * that labels each series line.
     *
     * @param Builder<Event> $query
     */
    protected function applySeriesDimension(Builder $query, string $seriesBy, array $filters): string
    {
        switch ($seriesBy) {
            case 'event_type':
                $query->leftJoin('event_types', 'events.event_type_id', '=', 'event_types.id');

                return "COALESCE(event_types.name, 'No type')";
            case 'venue':
                $query->leftJoin('entities as venue_entities', 'events.venue_id', '=', 'venue_entities.id');

                return "COALESCE(venue_entities.name, 'No venue')";
            case 'promoter':
                $query->leftJoin('entities as promoter_entities', 'events.promoter_id', '=', 'promoter_entities.id');

                return "COALESCE(promoter_entities.name, 'No promoter')";
            case 'series':
                $query->leftJoin('series as event_series', 'events.series_id', '=', 'event_series.id');

                return "COALESCE(event_series.name, 'No series')";
            case 'tag':
                $query->join('event_tag', 'event_tag.event_id', '=', 'events.id')
                    ->join('tags', 'tags.id', '=', 'event_tag.tag_id');

                return 'tags.name';
            case 'entity':
                $query->join('entity_event', 'entity_event.event_id', '=', 'events.id')
                    ->join('entities as related_entities', 'related_entities.id', '=', 'entity_event.entity_id');
                if (!empty($filters['entity_type_id'])) {
                    $query->where('related_entities.entity_type_id', $filters['entity_type_id']);
                }

                return 'related_entities.name';
            default:
                return "'Events'";
        }
    }

    /**
     * @param Builder<Event> $query
     */
    private function resolveTopSeries(Builder $query, string $seriesExpr, int $limit): array
    {
        return $query
            ->selectRaw("{$seriesExpr} as series_label, COUNT(*) as total_count")
            ->groupBy(DB::raw($seriesExpr))
            ->orderByDesc('total_count')
            ->limit($limit)
            ->pluck('series_label')
            ->map(fn ($label): string => (string) $label)
            ->all();
    }

    /**
     * @param Builder<Event> $query
     */
    private function queryDailyRows(Builder $query, string $seriesExpr, string $dateField, array $topSeries): Collection
    {
        if (empty($topSeries)) {
            return collect();
        }

        $dateExpr = "DATE(events.{$dateField})";

        return $query
            ->selectRaw("{$dateExpr} as period, {$seriesExpr} as series_label, COUNT(*) as event_count")
            ->groupBy(DB::raw($dateExpr), DB::raw($seriesExpr))
            ->havingRaw('series_label IN ('.implode(',', array_fill(0, count($topSeries), '?')).')', $topSeries)
            ->orderBy('period', 'asc')
            ->orderBy('series_label', 'asc')
            ->get()
            ->map(function (mixed $row): array {
                /** @var object{period: string, series_label: string, event_count: int} $row */
                return [
                    'period' => (string) $row->period,
                    'series_label' => (string) $row->series_label,
                    'event_count' => (int) $row->event_count,
                ];
            });
    }

    private function bucketRows(Collection $dailyRows, array $labels, string $groupBy): Collection
    {
        $bucketed = [];
        foreach ($dailyRows as $row) {
            $bucket = $this->getBucketLabel(Carbon::parse($row['period']), $groupBy);
            $bucketed[$bucket][$row['series_label']] = ($bucketed[$bucket][$row['series_label']] ?? 0) + $row['event_count'];
        }

        $rows = collect();
        foreach ($labels as $bucketLabel) {
            foreach ($bucketed[$bucketLabel] ?? [] as $seriesLabel => $count) {
                $rows->push((object) [
                    'period' => $bucketLabel,
                    'series_label' => $seriesLabel,
                    'event_count' => $count,
                ]);
            }
        }

        return $rows;
    }

    private function buildDatasets(array $topSeries, array $labels, Collection $rows): array
    {
        $datasetsMap = [];
        foreach ($topSeries as $seriesLabel) {
            $datasetsMap[$seriesLabel] = array_fill_keys($labels, 0);
        }

        foreach ($rows as $row) {
            if (isset($datasetsMap[$row->series_label][$row->period])) {
                $datasetsMap[$row->series_label][$row->period] = (int) $row->event_count;
            }
        }

        $datasets = [];
        foreach ($datasetsMap as $seriesLabel => $countsByPeriod) {
            $datasets[] = ['label' => $seriesLabel, 'data' => array_values($countsByPeriod)];
        }

        return $datasets;
    }

    protected function getGraphOptions(): array
    {
        return [
            'seriesByOptions' => [
                'total' => 'Total events',
                'event_type' => 'Event type',
                'venue' => 'Venue',
                'promoter' => 'Promoter',
                'series' => 'Series',
                'tag' => 'Tag',
                'entity' => 'Related entity',
            ],
            'dateFieldOptions' => [
                'start_at' => 'Event date',
                'created_at' => 'Created date',
            ],
            'eventTypeOptions' => ['' => 'All event types'] + EventType::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'entityTypeOptions' => ['' => 'All entity types'] + EntityType::orderBy('name', 'ASC')->pluck('name', 'id')->all(),
            'daysOptions' => [30 => 'Last 30 days', 90 => 'Last 90 days', 180 => 'Last 180 days', 365 => 'Last year', 1095 => 'Last 3 years'],
            'lineLimitOptions' => [5 => 'Top 5', 10 => 'Top 10', 20 => 'Top 20', 50 => 'Top 50'],
            'groupByOptions' => array_combine(self::ALLOWED_GROUP_BY, array_map('ucfirst', self::ALLOWED_GROUP_BY)),
        ];
    }
}
