@extends('layouts.app-tw')

@section('title','Search Results')

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Search Results</h1>
	@if(isset($search))
	<p class="text-gray-400">Results for: <span class="text-white font-semibold">"{{ $search }}"</span></p>
	@endif
</div>

<!-- Jump Links -->
<div class="mb-6 flex flex-wrap gap-2">
	@if (isset($entities) && $entitiesCount > 0)
	<a href="#entities-results" class="inline-flex items-center px-4 py-2 bg-dark-card border-2 border-primary text-white rounded-lg hover:bg-dark-border transition-colors text-sm font-medium">
		<i class="bi bi-people mr-2"></i>
		Entities ({{ $entitiesCount }})
	</a>
	@endif
	
	@if (isset($tags) && $tagsCount > 0)
	<a href="#tags-results" class="inline-flex items-center px-4 py-2 bg-dark-card border-2 border-primary text-white rounded-lg hover:bg-dark-border transition-colors text-sm font-medium">
		<i class="bi bi-tags mr-2"></i>
		Tags ({{ $tagsCount }})
	</a>
	@endif
	
	@if (isset($events) && count($events) > 0)
	<a href="#events-results" class="inline-flex items-center px-4 py-2 bg-dark-card border-2 border-primary text-white rounded-lg hover:bg-dark-border transition-colors text-sm font-medium">
		<i class="bi bi-calendar-event mr-2"></i>
		Events ({{ $eventsCount }})
	</a>
	@endif
	
	@if (isset($series) && count($series) > 0)
	<a href="#series-results" class="inline-flex items-center px-4 py-2 bg-dark-card border-2 border-primary text-white rounded-lg hover:bg-dark-border transition-colors text-sm font-medium">
		<i class="bi bi-collection mr-2"></i>
		Series ({{ $seriesCount }})
	</a>
	@endif
	
	@if (isset($threads) && count($threads) > 0)
	<a href="#threads-results" class="inline-flex items-center px-4 py-2 bg-dark-card border-2 border-primary text-white rounded-lg hover:bg-dark-border transition-colors text-sm font-medium">
		<i class="bi bi-chat mr-2"></i>
		Threads ({{ $threadsCount }})
	</a>
	@endif
</div>

<div class="space-y-6">
	<!-- Entities Results -->
	@if (isset($entities) && $entitiesCount > 0)
	<div id="entities-results" class="card-tw scroll-mt-6">
		<div class="p-4 border-b border-dark-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-white">Entities</h2>
				<span class="badge-tw bg-dark-card text-white">{{ $entitiesCount }}</span>
			</div>
			<button class="text-gray-400 hover:text-white" onclick="toggleSection('search-entities')">
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
		<i class="bi bi-people text-4xl text-gray-600 mb-3"></i>
		<p class="text-gray-400">No matching entities found.</p>
	</div>
	@endif

	<!-- Tags Results -->
	@if (isset($tags) && $tagsCount > 0)
	<div id="tags-results" class="card-tw scroll-mt-6">
		<div class="p-4 border-b border-dark-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-white">Tags</h2>
				<span class="badge-tw bg-dark-card text-white">{{ $tagsCount }}</span>
			</div>
			<button class="text-gray-400 hover:text-white" onclick="toggleSection('search-tags')">
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
	<div id="events-results" class="card-tw scroll-mt-6">
		<div class="p-4 border-b border-dark-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-white">Events</h2>
				<span class="badge-tw bg-dark-card text-white">{{ $eventsCount }}</span>
			</div>
			<button class="text-gray-400 hover:text-white" onclick="toggleSection('search-events')">
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
		<i class="bi bi-calendar-x text-4xl text-gray-600 mb-3"></i>
		<p class="text-gray-400">No matching events found.</p>
	</div>
	@endif

	<!-- Series Results -->
	@if (isset($series) && count($series) > 0)
	<div id="series-results" class="card-tw scroll-mt-6">
		<div class="p-4 border-b border-dark-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-white">Series</h2>
				<span class="badge-tw bg-dark-card text-white">{{ $seriesCount }}</span>
			</div>
			<button class="text-gray-400 hover:text-white" onclick="toggleSection('search-series')">
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
	<div id="threads-results" class="card-tw scroll-mt-6">
		<div class="p-4 border-b border-dark-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<h2 class="text-xl font-semibold text-white">Threads</h2>
				<span class="badge-tw bg-dark-card text-white">{{ $threadsCount }}</span>
			</div>
			<button class="text-gray-400 hover:text-white" onclick="toggleSection('search-threads')">
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
