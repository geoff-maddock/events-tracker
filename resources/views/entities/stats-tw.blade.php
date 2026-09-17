@extends('layouts.app-tw')

@section('title', $entity->name.' · Stats')

@section('content')

@php
	$current = $stats['periods'][$period];
	$reach = $stats['reach'][$period];

	// change against the previous period, or null when there is nothing to compare with
	$delta = function (array $pair) {
		if ($pair['previous'] === 0) {
			return null;
		}
		return (int) round(($pair['current'] - $pair['previous']) / $pair['previous'] * 100);
	};

	$cards = [
		['label' => 'Page views', 'icon' => 'bi-eye', 'pair' => $current['views'], 'hint' => 'Visits to this page, not counting bots, owners or admins.'],
		['label' => 'New followers', 'icon' => 'bi-star', 'pair' => $current['follows'], 'hint' => $stats['followers'].' '.Str::plural('follower', $stats['followers']).' in total.'],
		['label' => 'Ticket link clicks', 'icon' => 'bi-ticket-perforated', 'pair' => $current['clicks'], 'hint' => 'Clicks on ticket links for events you are part of. Updated nightly.'],
		['label' => 'RSVPs', 'icon' => 'bi-person-check', 'pair' => $current['responses'], 'hint' => 'Attending or interested responses on your events. Updated nightly.'],
	];
@endphp

<div class="container mx-auto max-w-6xl">
	<!-- Page Header -->
	<div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
		<div>
			<a href="{{ route('entities.show', $entity) }}" class="text-sm text-primary hover:text-primary/90 inline-flex items-center gap-1 mb-2">
				<i class="bi bi-arrow-left"></i> {{ $entity->name }}
			</a>
			<h1 class="text-3xl font-bold text-primary">Stats</h1>
			<p class="text-muted-foreground mt-1">
				How people find and use your page on {{ config('app.app_name') }}.
				@if ($stats['trackingSince'])
					Page views are counted since {{ $stats['trackingSince']->format('M j, Y') }}.
				@endif
			</p>
		</div>

		<div class="inline-flex rounded-lg border border-border overflow-hidden" role="group" aria-label="Period">
			@foreach ($periods as $days)
			<a href="{{ route('entities.stats', ['entity' => $entity, 'period' => $days]) }}"
				class="px-4 py-2 text-sm {{ $days === $period ? 'bg-primary text-primary-foreground' : 'bg-card text-foreground hover:bg-accent' }}"
				@if ($days === $period) aria-current="page" @endif>
				Last {{ $days }} days
			</a>
			@endforeach
		</div>
	</div>

	<!-- Headline numbers -->
	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
		@foreach ($cards as $card)
		@php
			$change = $delta($card['pair']);
		@endphp
		<div class="card-tw">
			<div class="p-4">
				<div class="flex items-center gap-2 text-sm text-muted-foreground">
					<i class="bi {{ $card['icon'] }}"></i>
					<span>{{ $card['label'] }}</span>
				</div>
				<div class="mt-2 flex items-baseline gap-2">
					<span class="text-3xl font-bold text-foreground">{{ number_format($card['pair']['current']) }}</span>
					@if ($change !== null)
					<span class="text-sm font-medium {{ $change >= 0 ? 'text-green-600 dark:text-green-400' : 'text-destructive' }}">
						{{ $change >= 0 ? '+' : '' }}{{ $change }}%
					</span>
					@endif
				</div>
				<p class="mt-1 text-xs text-muted-foreground">
					@if ($change === null)
						No data for the {{ $period }} days before.
					@else
						vs. {{ number_format($card['pair']['previous']) }} the {{ $period }} days before.
					@endif
				</p>
				<p class="mt-2 text-xs text-muted-foreground">{{ $card['hint'] }}</p>
			</div>
		</div>
		@endforeach
	</div>

	<!-- Trend chart -->
	<div class="card-tw mb-6">
		<div class="p-4">
			<h2 class="text-lg font-semibold text-foreground mb-3">Daily activity, last {{ max($periods) }} days</h2>
			@if (array_sum($stats['chart']['views']) + array_sum($stats['chart']['follows']) + array_sum($stats['chart']['clicks']) === 0)
				<p class="text-muted-foreground">Nothing to chart yet. Numbers show up here as people visit and follow your page.</p>
			@else
				<div class="relative h-72">
					<canvas id="entityStatsChart" aria-label="Daily page views, new followers and ticket clicks" role="img"></canvas>
				</div>
			@endif
		</div>
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
		<!-- Reach -->
		<div class="card-tw">
			<div class="p-4">
				<h2 class="text-lg font-semibold text-foreground mb-1">Your reach through {{ config('app.app_name') }}</h2>
				<p class="text-sm text-muted-foreground mb-4">Where your events were shared in the last {{ $period }} days.</p>
				<dl class="divide-y divide-border">
					<div class="flex justify-between py-2">
						<dt class="text-foreground"><i class="bi bi-envelope mr-2 text-muted-foreground"></i>Weekly email inboxes</dt>
						<dd class="font-semibold text-foreground">{{ number_format($reach['digest']) }}</dd>
					</div>
					<div class="flex justify-between py-2">
						<dt class="text-foreground"><i class="bi bi-instagram mr-2 text-muted-foreground"></i>Instagram posts</dt>
						<dd class="font-semibold text-foreground">{{ number_format($reach['instagram']) }}</dd>
					</div>
					<div class="flex justify-between py-2">
						<dt class="text-foreground"><i class="bi bi-discord mr-2 text-muted-foreground"></i>Discord posts</dt>
						<dd class="font-semibold text-foreground">{{ number_format($reach['discord']) }}</dd>
					</div>
				</dl>
			</div>
		</div>

		<!-- Upcoming -->
		<div class="card-tw">
			<div class="p-4">
				<h2 class="text-lg font-semibold text-foreground mb-1">Coming up</h2>
				<p class="text-sm text-muted-foreground mb-4">Events you're part of that haven't happened yet.</p>
				<dl class="divide-y divide-border">
					<div class="flex justify-between py-2">
						<dt class="text-foreground"><i class="bi bi-calendar-event mr-2 text-muted-foreground"></i>Upcoming events</dt>
						<dd class="font-semibold text-foreground">{{ number_format($stats['upcomingEvents']) }}</dd>
					</div>
					<div class="flex justify-between py-2">
						<dt class="text-foreground"><i class="bi bi-person-check mr-2 text-muted-foreground"></i>RSVPs on them</dt>
						<dd class="font-semibold text-foreground">{{ number_format($stats['upcomingResponses']) }}</dd>
					</div>
				</dl>
				<a href="{{ url('events/related-to/'.$entity->slug) }}" class="mt-4 inline-flex items-center text-sm text-primary hover:text-primary/90">
					See your events <i class="bi bi-arrow-right ml-1"></i>
				</a>
			</div>
		</div>
	</div>
</div>

@stop

@section('footer')
<script type="module">
	(function () {
		const canvas = document.getElementById('entityStatsChart');
		if (!canvas || !window.Chart) return;

		const series = @json($stats['chart']);
		const color = (hue) => `hsl(${hue}, 70%, 55%)`;

		new Chart(canvas, {
			type: 'line',
			data: {
				labels: series.labels,
				datasets: [
					{ label: 'Page views', data: series.views, borderColor: color(210), backgroundColor: color(210), tension: 0.25, pointRadius: 0 },
					{ label: 'New followers', data: series.follows, borderColor: color(45), backgroundColor: color(45), tension: 0.25, pointRadius: 0 },
					{ label: 'Ticket clicks', data: series.clicks, borderColor: color(150), backgroundColor: color(150), tension: 0.25, pointRadius: 0 },
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { mode: 'index', intersect: false },
				plugins: { legend: { position: 'bottom' } },
				scales: {
					y: { beginAtZero: true, ticks: { precision: 0 } },
					x: { ticks: { maxTicksLimit: 10 } },
				},
			},
		});
	})();
</script>
@stop
