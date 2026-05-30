@extends('layouts.app-tw')

@section('title','Search Results')

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Search Results</h1>
	@if(isset($search))
	<p class="text-muted-foreground">Results for: <span class="text-foreground font-semibold">"{{ $search }}"</span></p>
	@endif
</div>

{{-- Interpreted date filter banner: shown when the query contained a recognized
     date phrase alongside other text (a date-only query redirects instead). --}}
@if(!empty($interpreted['dateRange'] ?? null))
<div class="mb-6 rounded-md bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-800 px-4 py-3 flex items-center justify-between gap-4">
	<p class="text-sm text-indigo-800 dark:text-indigo-200">
		Looks like you're searching for a date:
		<span class="font-semibold">{{ $interpreted['dateRange']['label'] }}</span>
	</p>
	<a href="{{ route('events.index', ['filters' => ['start_at' => ['start' => $interpreted['dateRange']['start'], 'end' => $interpreted['dateRange']['end']]]]) }}"
		class="shrink-0 text-sm font-medium text-indigo-700 dark:text-indigo-300 hover:text-indigo-900 dark:hover:text-indigo-100 underline">
		View events for {{ $interpreted['dateRange']['label'] }} &rarr;
	</a>
</div>
@endif

<!-- Jump Links -->
<div class="mb-6 flex flex-wrap gap-2">
	@if (isset($entities) && $entitiesCount > 0)
	<a href="#entities-results" class="inline-flex items-center px-4 py-2 bg-card border-2 border-primary text-foreground rounded-lg hover:bg-accent transition-colors text-sm font-medium">
		<i class="bi bi-people mr-2"></i>
		Entities ({{ $entitiesCount }})
	</a>
	@endif
	
	@if (isset($events) && count($events) > 0)
	<a href="#events-results" class="inline-flex items-center px-4 py-2 bg-card border-2 border-primary text-foreground rounded-lg hover:bg-accent transition-colors text-sm font-medium">
		<i class="bi bi-calendar-event mr-2"></i>
		Events ({{ $eventsCount }})
	</a>
	@endif
	
	@if (isset($series) && count($series) > 0)
	<a href="#series-results" class="inline-flex items-center px-4 py-2 bg-card border-2 border-primary text-foreground rounded-lg hover:bg-accent transition-colors text-sm font-medium">
		<i class="bi bi-collection mr-2"></i>
		Series ({{ $seriesCount }})
	</a>
	@endif
	
	@if (isset($tags) && $tagsCount > 0)
	<a href="#tags-results" class="inline-flex items-center px-4 py-2 bg-card border-2 border-primary text-foreground rounded-lg hover:bg-accent transition-colors text-sm font-medium">
		<i class="bi bi-tags mr-2"></i>
		Tags ({{ $tagsCount }})
	</a>
	@endif

	@if (isset($threads) && count($threads) > 0)
	<a href="#threads-results" class="inline-flex items-center px-4 py-2 bg-card border-2 border-primary text-foreground rounded-lg hover:bg-accent transition-colors text-sm font-medium">
		<i class="bi bi-chat mr-2"></i>
		Threads ({{ $threadsCount }})
	</a>
	@endif
</div>

<div class="space-y-6">
	<!-- Entities Results -->
	@if (isset($entities) && $entitiesCount > 0)
	<div id="entities-results" class="card-tw scroll-mt-6">
		<div class="p-4 border-b border-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-foreground">Entities</h2>
				<span class="badge-tw bg-muted text-foreground">{{ $entitiesCount }}</span>
			</div>
			<button class="text-muted-foreground hover:text-foreground" onclick="toggleSection('search-entities')">
				<i class="bi bi-eye-fill"></i>
			</button>
		</div>
		<div id="search-entities" class="p-4">
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
				@foreach($entities as $entity)
				@include('entities.card-tw', ['entity' => $entity])
				@endforeach
			</div>
			<div class="mt-4">
				{!! $entities->appends(['keyword' => $search])->links('vendor.pagination.tailwind') !!}
			</div>
		</div>
	</div>
	@else
	<div class="card-tw p-6 text-center">
		<i class="bi bi-people text-4xl text-muted-foreground mb-3"></i>
		<p class="text-muted-foreground">No matching entities found.</p>
	</div>
	@endif

	<!-- Events Results -->
	@if (isset($events) && count($events) > 0)
	<div id="events-results" class="card-tw scroll-mt-6">
		<div class="p-4 border-b border-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-foreground">Events</h2>
				<span class="badge-tw bg-muted text-foreground">{{ $eventsCount }}</span>
			</div>
			<button class="text-muted-foreground hover:text-foreground" onclick="toggleSection('search-events')">
				<i class="bi bi-eye-fill"></i>
			</button>
		</div>
		<div id="search-events" class="p-4">
			@php
				$now = \Carbon\Carbon::now();
				$upcomingEvents = $events->getCollection()->filter(fn ($e) => $e->start_at && $e->start_at >= $now);
				$pastEvents     = $events->getCollection()->reject(fn ($e) => $e->start_at && $e->start_at >= $now);
			@endphp

			@if ($upcomingEvents->isNotEmpty())
			<div class="mb-5">
				<h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-3 flex items-center gap-2">
					<i class="bi bi-calendar-event"></i>
					Upcoming
					<span class="badge-tw bg-muted text-foreground text-xs">{{ $upcomingEvents->count() }}</span>
				</h3>
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
					@foreach($upcomingEvents as $event)
					@include('events.card-tw', ['event' => $event])
					@php unset($event); @endphp
					@endforeach
				</div>
			</div>
			@endif

			@if ($pastEvents->isNotEmpty())
			<div>
				@if ($upcomingEvents->isNotEmpty())
				<h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-3 flex items-center gap-2">
					<i class="bi bi-clock-history"></i>
					Past
					<span class="badge-tw bg-muted text-foreground text-xs">{{ $pastEvents->count() }}</span>
				</h3>
				@endif
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
					@foreach($pastEvents as $event)
					@include('events.card-tw', ['event' => $event])
					@php unset($event); @endphp
					@endforeach
				</div>
			</div>
			@endif

			<div class="mt-4">
				{!! $events->appends(['keyword' => $search])->links('vendor.pagination.tailwind') !!}
			</div>
		</div>
	</div>
	@else
	<div class="card-tw p-6 text-center">
		<i class="bi bi-calendar-x text-4xl text-muted-foreground mb-3"></i>
		<p class="text-muted-foreground">No matching events found.</p>
	</div>
	@endif

	<!-- Series Results -->
	@if (isset($series) && count($series) > 0)
	<div id="series-results" class="card-tw scroll-mt-6">
		<div class="p-4 border-b border-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-foreground">Series</h2>
				<span class="badge-tw bg-muted text-foreground">{{ $seriesCount }}</span>
			</div>
			<button class="text-muted-foreground hover:text-foreground" onclick="toggleSection('search-series')">
				<i class="bi bi-eye-fill"></i>
			</button>
		</div>
		<div id="search-series" class="p-4">
			@php
				$now = $now ?? \Carbon\Carbon::now();
				$upcomingSeries = $series->getCollection()->filter(fn ($s) => $s->start_at && $s->start_at >= $now);
				$pastSeries     = $series->getCollection()->reject(fn ($s) => $s->start_at && $s->start_at >= $now);
			@endphp

			@if ($upcomingSeries->isNotEmpty())
			<div class="mb-5">
				<h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-3 flex items-center gap-2">
					<i class="bi bi-calendar-event"></i>
					Upcoming
					<span class="badge-tw bg-muted text-foreground text-xs">{{ $upcomingSeries->count() }}</span>
				</h3>
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
					@foreach($upcomingSeries as $s)
					@include('series.card-tw', ['series' => $s])
					@php unset($s); @endphp
					@endforeach
				</div>
			</div>
			@endif

			@if ($pastSeries->isNotEmpty())
			<div>
				@if ($upcomingSeries->isNotEmpty())
				<h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-3 flex items-center gap-2">
					<i class="bi bi-clock-history"></i>
					Past
					<span class="badge-tw bg-muted text-foreground text-xs">{{ $pastSeries->count() }}</span>
				</h3>
				@endif
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
					@foreach($pastSeries as $s)
					@include('series.card-tw', ['series' => $s])
					@php unset($s); @endphp
					@endforeach
				</div>
			</div>
			@endif

			<div class="mt-4">
				{!! $series->appends(['keyword' => $search])->links('vendor.pagination.tailwind') !!}
			</div>
		</div>
	</div>
	@endif

		<!-- Tags Results -->
	@if (isset($tags) && $tagsCount > 0)
	<div id="tags-results" class="card-tw scroll-mt-6">
		<div class="p-4 border-b border-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-foreground">Tags</h2>
				<span class="badge-tw bg-muted text-foreground">{{ $tagsCount }}</span>
			</div>
			<button class="text-muted-foreground hover:text-foreground" onclick="toggleSection('search-tags')">
				<i class="bi bi-eye-fill"></i>
			</button>
		</div>
		<div id="search-tags" class="p-4">
			<div class="flex flex-wrap gap-2">
				@foreach($tags as $tag)
					@include('tags.grid-card-tw')
				@endforeach
			</div>
			<div class="mt-4">
				{!! $tags->appends(['keyword' => $search])->links('vendor.pagination.tailwind') !!}
			</div>
		</div>
	</div>
	@endif

	<!-- Threads Results -->
	@if (isset($threads) && count($threads) > 0)
	<div id="threads-results" class="card-tw scroll-mt-6">
		<div class="p-4 border-b border-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-foreground">Threads</h2>
				<span class="badge-tw bg-muted text-foreground">{{ $threadsCount }}</span>
			</div>
			<button class="text-muted-foreground hover:text-foreground" onclick="toggleSection('search-threads')">
				<i class="bi bi-eye-fill"></i>
			</button>
		</div>
		<div id="search-threads" class="p-4 space-y-4">
			@foreach($threads as $thread)
			@include('threads.card-tw', ['thread' => $thread])
			@php unset($thread); @endphp
			@endforeach
			<div class="mt-4">
				{!! $threads->appends(['keyword' => $search])->links('vendor.pagination.tailwind') !!}
			</div>
		</div>
	</div>
	@endif
</div>

@stop

@section('footer')
<script>
function toggleSection(sectionId) {
	const section = document.getElementById(sectionId);
	if (section) {
		section.classList.toggle('hidden');
	}
}
</script>
@stop
