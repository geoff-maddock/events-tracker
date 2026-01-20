@extends('layouts.app-tw')

@section('title')
Events @include('events.title-crumbs')
@endsection

@if (isset($past_events) && count($past_events) > 0)
@php
	$first = $past_events[0];
	if ($primary = $first->getPrimaryPhoto()) {
		$ogImage = Storage::disk('external')->url($primary->getStorageThumbnail());
	}
@endphp
@endif 

@if (isset($future_events) && count($future_events) > 0)
@php
	$first = $future_events[0];
	if ($primary = $first->getPrimaryPhoto()) {
		$ogImage = Storage::disk('external')->url($primary->getStorageThumbnail());
	}
@endphp
@endif 

@if (isset($ogImage))
@section('og-image')
{!! url('/').$ogImage !!}
@endsection
@endif

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Event Listings</h1>
	<p class="text-muted-foreground">Discover and explore upcoming events.</p>
</div>

<!-- Action Buttons -->
<div class="mb-6 flex flex-wrap gap-2">
	<a href="{!! URL::route('events.create') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
		<i class="bi bi-plus-lg mr-2"></i>
		Create Event
	</a>
	<a href="{!! URL::route('events.export') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors" target="_blank">
		<i class="bi bi-file-text mr-2"></i>
		Export TXT
	</a>
	<a href="{!! URL::route('events.indexIcal') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors" target="_blank">
		<i class="bi bi-calendar-event mr-2"></i>
		Export iCal
	</a>
</div>

<!-- Filters Section -->
<div class="mb-6">
	<button id="filters-toggle-btn" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
		<i class="bi bi-funnel mr-2"></i>
		<span id="filters-toggle-text">Show Filters</span>
		<i class="bi bi-chevron-down ml-2 transition-transform" id="filters-chevron"></i>
	</button>
	
	<!-- Active Filters Tags -->
	@if($hasFilter)
	<div class="inline-flex items-center gap-2 ml-4">
		@if(isset($filters['start_at']['start']))
		<span class="badge-tw badge-primary-tw">
			Date from {{ $filters['start_at']['start'] }}
			<button class="ml-1 hover:text-white">&times;</button>
		</span>
		@endif
		<a href="{{ url()->action('EventsController@rppReset') }}?key={!! $key ?? '' !!}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg">
			Clear All <i class="bi bi-x ml-1"></i>
		</a>
		<button class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg">
			Reset <i class="bi bi-arrow-clockwise ml-1"></i>
		</button>
	</div>
	@endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6 overflow-hidden">
	{!! Form::open(['route' => [$filterRoute ?? 'events.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
		<!-- Name Filter -->
		<div class="min-w-0">
			<label for="filter_name" class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
			<input type="text"
				name="filters[name]"
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="Event name...">
		</div>

		<!-- Venue Filter -->
		<div class="min-w-0">
			<label for="filter_venue" class="block text-sm font-medium text-muted-foreground mb-1">Venue</label>
			{!! Form::select('filter_venue', $venueOptions, ($filters['venue'] ?? null),
			[
				'data-theme' => 'tailwind',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a venue',
				'name' => 'filters[venue]',
				'id' => 'filter_venue'
			])
			!!}
		</div>

		<!-- Tag Filter -->
		<div class="min-w-0">
			<label for="filter_tag" class="block text-sm font-medium text-muted-foreground mb-1">Tag</label>
			{!! Form::select('filter_tag', $tagOptions, ($filters['tag'] ?? null),
			[
				'data-theme' => 'tailwind',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a tag',
				'name' => 'filters[tag]',
				'id' => 'filter_tag'
			])
			!!}
		</div>

		<!-- Related Entity Filter -->
		<div class="min-w-0">
			<label for="filter_related" class="block text-sm font-medium text-muted-foreground mb-1">Related Entity</label>
			{!! Form::select('filter_related', $relatedOptions, ($filters['related'] ?? null),
			[
				'data-theme' => 'tailwind',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select an entity',
				'name' => 'filters[related]',
				'id' => 'filter_related'
			])
			!!}
		</div>

		<!-- Event Type Filter -->
		<div class="min-w-0">
			<label for="filter_event_type" class="block text-sm font-medium text-muted-foreground mb-1">Type</label>
			{!! Form::select('filter_event_type', $eventTypeOptions, ($filters['event_type'] ?? null),
			[
				'data-theme' => 'tailwind',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a type',
				'name' => 'filters[event_type]',
				'id' => 'filter_event_type'
			])
			!!}
		</div>

		<!-- Date Range Filter -->
		<div class="min-w-0">
			<label class="block text-sm font-medium text-muted-foreground mb-1">Start Date</label>
			<div class="space-y-2">
				<div class="flex items-center gap-2">
					<span class="text-sm text-muted-foreground w-12 shrink-0">From:</span>
					<input type="date"
						name="filters[start_at][start]"
						value="{{ $filters['start_at']['start'] ?? '' }}"
						class="form-input-tw flex-1 min-w-0">
				</div>
				<div class="flex items-center gap-2">
					<span class="text-sm text-muted-foreground w-12 shrink-0">To:</span>
					<input type="date"
						name="filters[start_at][end]"
						value="{{ $filters['start_at']['end'] ?? '' }}"
						class="form-input-tw flex-1 min-w-0">
				</div>
			</div>
		</div>
	</div>

	<!-- Filter Actions -->
	<div class="flex gap-2 mt-4">
		<button type="submit" class="px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
			Apply
		</button>
		{!! Form::close() !!}
		{!! Form::open(['route' => ['events.reset'], 'method' => 'GET']) !!}
		{!! Form::hidden('redirect', $redirect ?? 'events.index') !!}
		{!! Form::hidden('key', $key ?? 'internal_event_index') !!}
		<button type="submit" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
			Reset
		</button>
		{!! Form::close() !!}
	</div>
</div>

<!-- Results Bar -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
	<!-- Results Count -->
	<div class="text-sm text-muted-foreground">
		@if(isset($events))
		Showing {{ $events->firstItem() ?? 0 }} to {{ $events->lastItem() ?? 0 }} of {{ $events->total() }} results
		@endif
	</div>

	<!-- Sort Controls -->
	<div class="flex items-center gap-4">
		<form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
			<select name="limit" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($limitOptions as $value => $label)
				<option value="{{ $value }}" {{ ($limit ?? 10) == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<span class="text-muted-foreground text-sm">Sort by:</span>
			<select name="sort" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($sortOptions as $value => $label)
				<option value="{{ $value }}" {{ ($sort ?? 'events.start_at') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<select name="direction" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($directionOptions as $value => $label)
				<option value="{{ $value }}" {{ ($direction ?? 'desc') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
		</form>
	</div>

	<!-- Pagination (top) -->
	@if(isset($events) && $events->hasPages())
	<div class="flex items-center gap-1">
		@if($events->onFirstPage())
		<span class="px-3 py-1 text-muted-foreground/50 cursor-not-allowed">&lt; Previous</span>
		@else
		<a href="{{ $events->previousPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">&lt; Previous</a>
		@endif

		@foreach($events->getUrlRange(max(1, $events->currentPage() - 2), min($events->lastPage(), $events->currentPage() + 2)) as $page => $url)
		<a href="{{ $url }}" class="px-3 py-1 rounded {{ $page == $events->currentPage() ? 'bg-accent text-foreground border border-primary' : 'text-muted-foreground hover:bg-card' }}">{{ $page }}</a>
		@endforeach

		@if($events->hasMorePages())
		<a href="{{ $events->nextPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">Next &gt;</a>
		@else
		<span class="px-3 py-1 text-muted-foreground/50 cursor-not-allowed">Next &gt;</span>
		@endif
	</div>
	@endif
</div>

<!-- Events Grid -->
@if (isset($events) && count($events) > 0)
<div class="grid grid-cols-1 md:grid-cols-2 event-3col:grid-cols-3 event-4col:grid-cols-4 gap-6">
	@foreach ($events as $event)
	@include('events.card-tw', ['event' => $event])
	@endforeach
</div>
@else
<div class="text-center py-12">
	<i class="bi bi-calendar-x text-6xl text-muted-foreground/60 mb-4"></i>
	<p class="text-muted-foreground">No matching events found.</p>
	<a href="{{ url('/events') }}" class="mt-4 inline-flex items-center text-primary hover:text-primary/90">
		<i class="bi bi-arrow-left mr-2"></i>
		View all events
	</a>
</div>
@endif


@stop

@section('footer')
<script>
	// Filter toggle functionality
	document.getElementById('filters-toggle-btn')?.addEventListener('click', function() {
		const panel = document.getElementById('filter-panel');
		const text = document.getElementById('filters-toggle-text');
		const chevron = document.getElementById('filters-chevron');
		
		panel.classList.toggle('hidden');
		
		if (panel.classList.contains('hidden')) {
			text.textContent = 'Show Filters';
			chevron.classList.remove('rotate-180');
		} else {
			text.textContent = 'Hide Filters';
			chevron.classList.add('rotate-180');
		}
	});
</script>
@include('partials.filter-js')
@endsection
