@extends('layouts.app-tw')

@section('title', 'Activity Graph')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-primary mb-2">Activity Graph</h1>
    <p class="text-muted-foreground">Admin-only activity trends from the activity log.</p>
</div>

<div class="card-tw p-4 mb-6">
    <form method="GET" action="{{ route('activities.graph') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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

        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">Apply</button>
            <a href="{{ route('activities.graph') }}" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">Reset</a>
            <a href="{{ route('activities.graph.export', request()->query()) }}" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">Export CSV</a>
        </div>
    </form>
</div>

<div class="card-tw p-4 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold">Activity counts by date</h2>
        <span class="text-sm text-muted-foreground">{{ $startDate->toDateString() }} to {{ $endDate->toDateString() }}</span>
    </div>
    <canvas id="activityGraph" height="120"></canvas>
</div>

<div class="card-tw p-4">
    <h2 class="text-lg font-semibold mb-3">Data points</h2>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b border-border">
                    <th class="py-2 pr-4">Date</th>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const labels = @json($labels);
        const datasets = @json($datasets).map((dataset, index) => {
            const hue = (index * 57) % 360;
            return {
                ...dataset,
                borderColor: `hsl(${hue}, 70%, 55%)`,
                backgroundColor: `hsl(${hue}, 70%, 55%)`,
                tension: 0.25,
                fill: false
            };
        });

        const canvas = document.getElementById('activityGraph');
        if (!canvas || datasets.length === 0) {
            return;
        }

        new Chart(canvas, {
            type: 'line',
            data: { labels, datasets },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Count' } },
                    x: { title: { display: true, text: 'Date' } }
                }
            }
        });
    })();
</script>
@endsection
