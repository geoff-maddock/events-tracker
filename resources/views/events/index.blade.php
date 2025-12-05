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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Event Listings</h1>
	<p class="text-gray-400">Discover and explore upcoming events.</p>
</div>

<!-- Create Event Button -->
<div class="mb-6">
	<a href="{!! URL::route('events.create') !!}" class="inline-flex items-center px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors">
		<i class="bi bi-plus-lg mr-2"></i>
		Create Event
	</a>
</div>

<!-- Filters Section -->
<div class="mb-6">
	<button id="filters-toggle-btn" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
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
		<a href="{{ url()->action('EventsController@rppReset') }}?key={!! $key ?? '' !!}" class="inline-flex items-center px-3 py-1 text-sm text-gray-300 hover:text-white border border-dark-border rounded-lg">
			Clear All <i class="bi bi-x ml-1"></i>
		</a>
		<button class="inline-flex items-center px-3 py-1 text-sm text-gray-300 hover:text-white border border-dark-border rounded-lg">
			Reset <i class="bi bi-arrow-clockwise ml-1"></i>
		</button>
	</div>
	@endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-dark-surface border border-dark-border rounded-lg p-4 mb-6">
	{!! Form::open(['route' => [$filterRoute ?? 'events.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
	
	<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
		<!-- Name Filter -->
		<div>
			<label for="filter_name" class="block text-sm font-medium text-gray-300 mb-1">Name</label>
			<input type="text" 
				name="filters[name]" 
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="Event name...">
		</div>

		<!-- Venue Filter -->
		<div>
			<label for="filter_venue" class="block text-sm font-medium text-gray-300 mb-1">Venue</label>
			{!! Form::select('filter_venue', $venueOptions, ($filters['venue'] ?? null),
			[
				'data-theme' => 'bootstrap-5',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a venue',
				'name' => 'filters[venue]',
				'id' => 'filter_venue'
			])
			!!}
		</div>

		<!-- Tag Filter -->
		<div>
			<label for="filter_tag" class="block text-sm font-medium text-gray-300 mb-1">Tag</label>
			{!! Form::select('filter_tag', $tagOptions, ($filters['tag'] ?? null),
			[
				'data-theme' => 'bootstrap-5',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a tag',
				'name' => 'filters[tag]',
				'id' => 'filter_tag'
			])
			!!}
		</div>

		<!-- Related Entity Filter -->
		<div>
			<label for="filter_related" class="block text-sm font-medium text-gray-300 mb-1">Related Entity</label>
			{!! Form::select('filter_related', $relatedOptions, ($filters['related'] ?? null),
			[
				'data-theme' => 'bootstrap-5',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select an entity',
				'name' => 'filters[related]',
				'id' => 'filter_related'
			])
			!!}
		</div>

		<!-- Event Type Filter -->
		<div>
			<label for="filter_event_type" class="block text-sm font-medium text-gray-300 mb-1">Type</label>
			{!! Form::select('filter_event_type', $eventTypeOptions, ($filters['event_type'] ?? null),
			[
				'data-theme' => 'bootstrap-5',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a type',
				'name' => 'filters[event_type]',
				'id' => 'filter_event_type'
			])
			!!}
		</div>

		<!-- Date Range Filter -->
		<div>
			<label class="block text-sm font-medium text-gray-300 mb-1">Start Date</label>
			<div class="space-y-2">
				<div class="flex items-center gap-2">
					<span class="text-sm text-gray-400 w-12">From:</span>
					<input type="date" 
						name="filters[start_at][start]" 
						value="{{ $filters['start_at']['start'] ?? '' }}"
						class="form-input-tw flex-1">
				</div>
				<div class="flex items-center gap-2">
					<span class="text-sm text-gray-400 w-12">To:</span>
					<input type="date" 
						name="filters[start_at][end]" 
						value="{{ $filters['start_at']['end'] ?? '' }}"
						class="form-input-tw flex-1">
				</div>
			</div>
		</div>
	</div>

	<!-- Filter Actions -->
	<div class="flex gap-2 mt-4">
		<button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
			Apply
		</button>
		{!! Form::close() !!}
		{!! Form::open(['route' => ['events.reset'], 'method' => 'GET']) !!}
		{!! Form::hidden('redirect', $redirect ?? 'events.index') !!}
		{!! Form::hidden('key', $key ?? 'internal_event_index') !!}
		<button type="submit" class="px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors">
			Reset
		</button>
		{!! Form::close() !!}
	</div>
</div>

<!-- Results Bar -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
	<!-- Results Count -->
	<div class="text-sm text-gray-400">
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
			<span class="text-gray-400 text-sm">Sort by:</span>
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
		<span class="px-3 py-1 text-gray-500 cursor-not-allowed">&lt; Previous</span>
		@else
		<a href="{{ $events->previousPageUrl() }}" class="px-3 py-1 text-gray-300 hover:text-white">&lt; Previous</a>
		@endif
		
		@foreach($events->getUrlRange(max(1, $events->currentPage() - 2), min($events->lastPage(), $events->currentPage() + 2)) as $page => $url)
		<a href="{{ $url }}" class="px-3 py-1 rounded {{ $page == $events->currentPage() ? 'bg-primary text-white' : 'text-gray-300 hover:bg-dark-card' }}">{{ $page }}</a>
		@endforeach
		
		@if($events->hasMorePages())
		<a href="{{ $events->nextPageUrl() }}" class="px-3 py-1 text-gray-300 hover:text-white">Next &gt;</a>
		@else
		<span class="px-3 py-1 text-gray-500 cursor-not-allowed">Next &gt;</span>
		@endif
	</div>
	@endif
</div>

<!-- Events Grid -->
@if (isset($events) && count($events) > 0)
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
	@foreach ($events as $event)
	@include('events.card-tw', ['event' => $event])
	@endforeach
</div>

<!-- Pagination (bottom) -->
<div class="mt-6">
	{!! $events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
</div>
@else
<div class="text-center py-12">
	<i class="bi bi-calendar-x text-6xl text-gray-600 mb-4"></i>
	<p class="text-gray-400">No matching events found.</p>
	<a href="{{ url('/events') }}" class="mt-4 inline-flex items-center text-primary hover:text-primary-hover">
		<i class="bi bi-arrow-left mr-2"></i>
		View all events
	</a>
</div>
@endif

<!-- Past Events Section -->
@if (isset($past_events) && count($past_events) > 0)
<div class="mt-12">
	<h2 class="text-2xl font-bold text-primary mb-6">
		<a href="{{ url('/events/past') }}" class="hover:text-primary-hover">Past Events</a>
	</h2>
	<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
		@foreach ($past_events as $event)
		@include('events.card-tw', ['event' => $event])
		@endforeach
	</div>
	<div class="mt-6">
		{!! $past_events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
	</div>
</div>
@endif

<!-- Future Events Section -->
@if (isset($future_events) && count($future_events) > 0)
<div class="mt-12">
	<h2 class="text-2xl font-bold text-primary mb-6">
		<a href="{{ url('/events/future') }}" class="hover:text-primary-hover">Future Events</a>
	</h2>
	<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
		@foreach ($future_events as $event)
		@include('events.card-tw', ['event' => $event])
		@endforeach
	</div>
	<div class="mt-6">
		{!! $future_events->onEachSide(2)->links('vendor.pagination.tailwind') !!}
	</div>
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
