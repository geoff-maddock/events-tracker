@extends('layouts.app-tw')

@section('title', 'Activity Graph')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-primary mb-2">Activity Graph</h1>
    <p class="text-muted-foreground">Admin-only activity trends from the activity log.</p>
</div>

<div class="card-tw p-4 mb-6">
    <form method="GET" action="{{ route('activities.graph') }}" id="graphForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label for="days" class="block text-sm text-muted-foreground mb-1">Range</label>
            <select id="days" name="days" class="form-select-tw">
                @foreach($daysOptions as $value => $label)
                    <option value="{{ $value }}" {{ (int) ($filters['days'] ?? 7) === (int) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="start_date" class="block text-sm text-muted-foreground mb-1">Start date</label>
            <input id="start_date" name="start_date" type="date" value="{{ $filters['start_date'] }}" class="form-input-tw">
        </div>
        <div>
            <label for="end_date" class="block text-sm text-muted-foreground mb-1">End date</label>
            <input id="end_date" name="end_date" type="date" value="{{ $filters['end_date'] }}" class="form-input-tw">
        </div>
        <div>
            <label for="line_limit" class="block text-sm text-muted-foreground mb-1">Activity lines</label>
            <select id="line_limit" name="line_limit" class="form-select-tw">
                @foreach($lineLimitOptions as $value => $label)
                    <option value="{{ $value }}" {{ (int) ($filters['line_limit'] ?? 10) === (int) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="group_by" class="block text-sm text-muted-foreground mb-1">Group by</label>
            <select id="group_by" name="group_by" class="form-select-tw">
                @foreach($groupByOptions as $value => $label)
                    <option value="{{ $value }}" {{ ($filters['group_by'] ?? 'day') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="action_id" class="block text-sm text-muted-foreground mb-1">Action</label>
            <select id="action_id" name="action_id" class="form-select-tw">
                @foreach($actionOptions as $value => $label)
                    <option value="{{ $value }}" {{ (string) ($filters['action_id'] ?? '') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="object_table" class="block text-sm text-muted-foreground mb-1">Table</label>
            <select id="object_table" name="object_table" class="form-select-tw">
                @foreach($tableOptions as $value => $label)
                    <option value="{{ $value }}" {{ (string) ($filters['object_table'] ?? '') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="user_id" class="block text-sm text-muted-foreground mb-1">User</label>
            <select id="user_id" name="user_id" class="form-select-tw">
                @foreach($userOptions as $value => $label)
                    <option value="{{ $value }}" {{ (string) ($filters['user_id'] ?? '') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-4 flex flex-wrap items-center gap-2">
            <button type="submit" id="applyBtn" class="px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">Apply</button>
            <span id="loadingIndicator" class="hidden text-sm text-muted-foreground">Loading&hellip;</span>
            <a href="{{ route('activities.graph') }}" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">Reset</a>
            <a href="{{ route('activities.graph.export', array_filter([
                    'days'         => $filters['days'] ?? null,
                    'start_date'   => $filters['start_date'] ?? null,
                    'end_date'     => $filters['end_date'] ?? null,
                    'action_id'    => $filters['action_id'] ?? null,
                    'object_table' => $filters['object_table'] ?? null,
                    'user_id'      => $filters['user_id'] ?? null,
                    'line_limit'   => $filters['line_limit'] ?? null,
                    'group_by'     => $filters['group_by'] ?? null,
                ], fn($v) => $v !== null && $v !== '')) }}"
               class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">Export CSV</a>
        </div>
    </form>
</div>

<div class="card-tw p-4 mb-6">
    <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
        <div>
            <h2 class="text-lg font-semibold">Activity counts by {{ $groupBy }}</h2>
            <span class="text-sm text-muted-foreground">
                {{ $startDate->toDateString() }} to {{ $endDate->toDateString() }}
                &mdash; <strong>{{ number_format($total) }}</strong> total {{ Str::plural('activity', $total) }}
            </span>
        </div>
        <div class="flex gap-1" id="chartTypeToggle">
            <button data-type="line"    class="chart-type-btn px-3 py-1 text-sm rounded-lg border border-border bg-accent text-foreground">Line</button>
            <button data-type="bar"     class="chart-type-btn px-3 py-1 text-sm rounded-lg border border-border bg-card text-foreground hover:bg-accent transition-colors">Bar</button>
            <button data-type="stacked" class="chart-type-btn px-3 py-1 text-sm rounded-lg border border-border bg-card text-foreground hover:bg-accent transition-colors">Stacked</button>
        </div>
    </div>
    <canvas id="activityGraph" height="120"></canvas>
    <p id="activityGraphEmpty" class="hidden mt-3 text-sm text-muted-foreground">No data available for the selected filters.</p>
</div>

<div class="card-tw p-4">
    <h2 class="text-lg font-semibold mb-3">Data points</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b border-border">
                    <th class="py-2 pr-4">Period</th>
                    <th class="py-2 pr-4">Activity type</th>
                    <th class="py-2 pr-4">Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-b border-border/40">
                        <td class="py-2 pr-4">{{ $row->activity_date }}</td>
                        <td class="py-2 pr-4">{{ $row->activity_type }}</td>
                        <td class="py-2 pr-4">{{ $row->activity_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-3 text-muted-foreground">No activity found for these filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('footer')
<script type="module">
    (function () {
        const labels = @json($labels);
        const groupBy = @json($groupBy);
        const rawDatasets = @json($datasets);

        const canvas = document.getElementById('activityGraph');
        const emptyMessage = document.getElementById('activityGraphEmpty');
        const daysSelect = document.getElementById('days');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const applyBtn = document.getElementById('applyBtn');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const graphForm = document.getElementById('graphForm');

        // ── date ↔ days bi-directional sync ────────────────────────────────
        const toInputDate = (date) => {
            const y = date.getFullYear();
            const m = `${date.getMonth() + 1}`.padStart(2, '0');
            const d = `${date.getDate()}`.padStart(2, '0');
            return `${y}-${m}-${d}`;
        };

        if (daysSelect && startDateInput && endDateInput) {
            daysSelect.addEventListener('change', () => {
                const days = Number(daysSelect.value || 7);
                if (!Number.isFinite(days) || days < 1) return;
                const today = new Date();
                const end = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                const start = new Date(end);
                start.setDate(start.getDate() - (days - 1));
                startDateInput.value = toInputDate(start);
                endDateInput.value = toInputDate(end);
            });

            const clearDaysOnManualDate = () => {
                if (daysSelect.value !== '') {
                    daysSelect.value = '';
                }
            };
            startDateInput.addEventListener('change', clearDaysOnManualDate);
            endDateInput.addEventListener('change', clearDaysOnManualDate);
        }

        // ── loading indicator ───────────────────────────────────────────────
        if (graphForm && loadingIndicator && applyBtn) {
            graphForm.addEventListener('submit', () => {
                loadingIndicator.classList.remove('hidden');
                applyBtn.disabled = true;
            });
        }

        if (!canvas) return;

        if (rawDatasets.length === 0) {
            emptyMessage?.classList.remove('hidden');
            return;
        }

        // ── chart rendering ─────────────────────────────────────────────────
        const coloredDatasets = (stacked) => rawDatasets.map((dataset, index) => {
            const hue = (index * 57) % 360;
            return {
                ...dataset,
                borderColor: `hsl(${hue}, 70%, 55%)`,
                backgroundColor: stacked ? `hsl(${hue}, 70%, 55%)` : `hsl(${hue}, 70%, 55%)`,
                tension: 0.25,
                fill: stacked,
            };
        });

        let currentType = 'line';
        let currentStacked = false;

        const buildConfig = (type, stacked) => ({
            type: type === 'stacked' ? 'bar' : type,
            data: { labels, datasets: coloredDatasets(stacked) },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: stacked,
                        title: { display: true, text: 'Count' },
                    },
                    x: {
                        stacked: stacked,
                        title: { display: true, text: `Period (${groupBy})` },
                    },
                },
            },
        });

        let chart = new Chart(canvas, buildConfig(currentType, currentStacked));

        // ── chart type toggle ───────────────────────────────────────────────
        document.querySelectorAll('.chart-type-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.type;
                currentType = type;
                currentStacked = type === 'stacked';

                document.querySelectorAll('.chart-type-btn').forEach(b => {
                    b.classList.toggle('bg-accent', b === btn);
                    b.classList.toggle('bg-card', b !== btn);
                });

                chart.destroy();
                chart = new Chart(canvas, buildConfig(currentType, currentStacked));
            });
        });
    })();
</script>
@endsection
