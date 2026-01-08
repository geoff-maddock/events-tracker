@extends('app')

@section('title','Search Results')

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Search Results</h1>
	@if(isset($search))
	<p class="text-muted-foreground">Results for: <span class="text-foreground font-semibold">"{{ $search }}"</span></p>
	@endif
</div>

<!-- Action Menu -->
<div class="mb-6 flex flex-wrap gap-2">
	<a href="{!! URL::route('events.index') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Event Index
	</a>
	<a href="{!! URL::route('calendar') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Event Calendar
	</a>
	<a href="{!! URL::route('events.create') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors text-sm">
		<i class="bi bi-plus-lg mr-2"></i>
		Add Event
	</a>
	<a href="{!! URL::route('series.create') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors text-sm">
		<i class="bi bi-plus-lg mr-2"></i>
		Add Series
	</a>
	<a href="{!! URL::route('entities.create') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors text-sm">
		<i class="bi bi-plus-lg mr-2"></i>
		Add Entity
	</a>
	<a href="{!! URL::route('threads.create') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors text-sm">
		<i class="bi bi-plus-lg mr-2"></i>
		Add Thread
	</a>
</div>

<div class="space-y-6">
	<!-- Entities Results -->
	@if (isset($entities) && $entitiesCount > 0)
	<div class="card-tw">
		<div class="p-4 border-b border-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-foreground">Entities</h2>
				<span class="badge-tw bg-card text-foreground">{{ $entitiesCount }}</span>
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
		<i class="bi bi-people text-4xl text-muted-foreground/60 mb-3"></i>
		<p class="text-muted-foreground">No matching entities found.</p>
	</div>
	@endif

	<!-- Tags Results -->
	@if (isset($tags) && $tagsCount > 0)
	<div class="card-tw">
		<div class="p-4 border-b border-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-foreground">Tags</h2>
				<span class="badge-tw bg-card text-foreground">{{ $tagsCount }}</span>
			</div>
			<button class="text-muted-foreground hover:text-foreground" onclick="toggleSection('search-tags')">
				<i class="bi bi-eye-fill"></i>
			</button>
		</div>
		<div id="search-tags" class="p-4">
			<div class="flex flex-wrap gap-2">
				@foreach($tags as $tag)
				<a href="/tags/{{ $tag->slug }}" class="badge-tw badge-primary-tw hover:bg-primary/30">
					{{ $tag->name }}
				</a>
				@endforeach
			</div>
			<div class="mt-4">
				{!! $tags->appends(['keyword' => $search])->links('vendor.pagination.tailwind') !!}
			</div>
		</div>
	</div>
	@endif

	<!-- Events Results -->
	@if (isset($events) && count($events) > 0)
	<div class="card-tw">
		<div class="p-4 border-b border-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-foreground">Events</h2>
				<span class="badge-tw bg-card text-foreground">{{ $eventsCount }}</span>
			</div>
			<button class="text-muted-foreground hover:text-foreground" onclick="toggleSection('search-events')">
				<i class="bi bi-eye-fill"></i>
			</button>
		</div>
		<div id="search-events" class="p-4">
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
				@foreach($events as $event)
				@include('events.card-tw', ['event' => $event])
				@endforeach
			</div>
			<div class="mt-4">
				{!! $events->appends(['keyword' => $search])->links('vendor.pagination.tailwind') !!}
			</div>
		</div>
	</div>
	@else
	<div class="card-tw p-6 text-center">
		<i class="bi bi-calendar-x text-4xl text-muted-foreground/60 mb-3"></i>
		<p class="text-muted-foreground">No matching events found.</p>
	</div>
	@endif

	<!-- Series Results -->
	@if (isset($series) && count($series) > 0)
	<div class="card-tw">
		<div class="p-4 border-b border-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-foreground">Series</h2>
				<span class="badge-tw bg-card text-foreground">{{ $seriesCount }}</span>
			</div>
			<button class="text-muted-foreground hover:text-foreground" onclick="toggleSection('search-series')">
				<i class="bi bi-eye-fill"></i>
			</button>
		</div>
		<div id="search-series" class="p-4">
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
				@foreach($series as $s)
				@include('series.card-tw', ['series' => $s])
				@endforeach
			</div>
			<div class="mt-4">
				{!! $series->appends(['keyword' => $search])->links('vendor.pagination.tailwind') !!}
			</div>
		</div>
	</div>
	@endif

	<!-- Threads Results -->
	@if (isset($threads) && count($threads) > 0)
	<div class="card-tw">
		<div class="p-4 border-b border-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-foreground">Threads</h2>
				<span class="badge-tw bg-card text-foreground">{{ $threadsCount }}</span>
			</div>
			<button class="text-muted-foreground hover:text-foreground" onclick="toggleSection('search-threads')">
				<i class="bi bi-eye-fill"></i>
			</button>
		</div>
		<div id="search-threads" class="p-4 space-y-4">
			@foreach($threads as $thread)
			@include('threads.card-tw', ['thread' => $thread])
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
