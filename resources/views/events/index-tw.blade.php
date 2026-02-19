@extends('layouts.app-tw')

@section('title')
Events @include('events.title-crumbs')
@endsection

@if (isset($events) && count($events) > 0)
@php
	$first = $events[0];
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
	<h1 class="text-3xl font-bold text-primary mb-2">Event Listings @include('events.crumbs-tw')</h1>
	<p class="text-muted-foreground">Discover and explore upcoming events.</p>
</div>

<!-- Action Buttons -->
<div class="mb-6 flex flex-wrap gap-2">
	<a href="{!! URL::route('events.create') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
		<i class="bi bi-plus-lg mr-2"></i>
		Create Event
	</a>
	<a href="{!! URL::route('events.export') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm" target="_blank">
		<i class="bi bi-download mr-2"></i>
		Export TXT
	</a>
	<a href="{!! URL::route('events.indexIcal') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm" target="_blank">
		<i class="bi bi-calendar-event mr-2"></i>
		Export iCal
	</a>
</div>

<!-- Filters Section -->
<div class="mb-6 flex flex-wrap items-start gap-2">
	<button id="filters-toggle-btn" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
		<i class="bi bi-funnel mr-2"></i>
		<span id="filters-toggle-text">@if($hasFilter) Hide @else Show @endif Filters</span>
		<i class="bi bi-chevron-down ml-2 transition-transform @if($hasFilter) rotate-180 @endif" id="filters-chevron"></i>
	</button>
	
	<!-- Active Filters Badges (shown when filters are hidden) -->
	@if($hasFilter)
	<div id="active-filters-badges" class="@if($hasFilter) hidden @endif flex flex-wrap items-center gap-2">
		@if(!empty($filters['name']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Name: {{ $filters['name'] }}
		</span>
		@endif
		@if(!empty($filters['venue']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Venue: {{ $venueOptions[$filters['venue']] ?? 'Unknown' }}
		</span>
		@endif
		@if(!empty($filters['tag']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Tag: {{ collect((array) $filters['tag'])->filter()->map(fn ($tag) => $tagOptions[$tag] ?? $tag)->implode(', ') }}
		</span>
		@endif
		@if(!empty($filters['related']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Entity: {{ $relatedOptions[$filters['related']] ?? 'Unknown' }}
		</span>
		@endif
		@if(!empty($filters['event_type']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Type: {{ $eventTypeOptions[$filters['event_type']] ?? 'Unknown' }}
		</span>
		@endif
		@if(isset($filters['start_at']['start']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Date from: {{ $filters['start_at']['start'] }}
		</span>
		@endif
		@if(isset($filters['start_at']['end']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Date to: {{ $filters['start_at']['end'] }}
		</span>
		@endif
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
			<label for="filter_tag" class="block text-sm font-medium text-muted-foreground mb-1">Tags</label>
			{!! Form::select('filters[tag][]', $tagOptions, ($filters['tag'] ?? null),
			[
				'data-theme' => 'tailwind',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select tags',
				'id' => 'filter_tag',
				'multiple' => true
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
		@if($hasFilter)
		<button type="button" id="copy-filter-url-btn" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors flex items-center gap-2">
			<i class="bi bi-link-45deg"></i>
			<span>Copy Filter URL</span>
		</button>
		@endif
	</div>
</div>

@include('events.index-sort-pagination')

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

<br>
@include('events.index-sort-pagination')

@stop

@section('footer')
<script>
	// Filter toggle functionality
	document.getElementById('filters-toggle-btn')?.addEventListener('click', function() {
		const panel = document.getElementById('filter-panel');
		const badges = document.getElementById('active-filters-badges');
		const text = document.getElementById('filters-toggle-text');
		const chevron = document.getElementById('filters-chevron');
		
		panel.classList.toggle('hidden');
		
		if (panel.classList.contains('hidden')) {
			text.textContent = 'Show Filters';
			chevron.classList.remove('rotate-180');
			// Show badges when filters are hidden
			if (badges) {
				badges.classList.remove('hidden');
			}
		} else {
			text.textContent = 'Hide Filters';
			chevron.classList.add('rotate-180');
			// Hide badges when filters are shown
			if (badges) {
				badges.classList.add('hidden');
			}
		}
	});

	// Copy filter URL functionality
	document.getElementById('copy-filter-url-btn')?.addEventListener('click', function() {
		// Build the filter URL with current filter values
		const filters = @json($filters ?? []);
		const sort = @json($sort ?? '');
		const direction = @json($direction ?? '');
		const limit = @json($limit ?? null);
		
		// Build query parameters
		const params = new URLSearchParams();
		
		// Add filters
		if (filters && Object.keys(filters).length > 0) {
			for (const [key, value] of Object.entries(filters)) {
				if (value !== null && value !== '' && value !== undefined) {
					if (Array.isArray(value)) {
						// Handle array values (like tags)
						value.forEach(v => {
							if (v !== null && v !== '') {
								params.append(`filters[${key}][]`, v);
							}
						});
					} else if (typeof value === 'object' && value !== null) {
						// Handle nested objects (like date ranges)
						for (const [subKey, subValue] of Object.entries(value)) {
							if (subValue !== null && subValue !== '') {
								params.append(`filters[${key}][${subKey}]`, subValue);
							}
						}
					} else {
						params.append(`filters[${key}]`, value);
					}
				}
			}
		}
		
		// Add sorting parameters
		if (sort) {
			params.append('sort', sort);
		}
		if (direction) {
			params.append('direction', direction);
		}
		if (limit) {
			params.append('limit', limit);
		}
		
		// Build the full URL
		const baseUrl = '{{ Route::has('events.applyFilterFromUrl') ? route('events.applyFilterFromUrl') : route('events.filter') }}';
		const fullUrl = `${baseUrl}?${params.toString()}`;
		
		// Copy to clipboard
		navigator.clipboard.writeText(fullUrl).then(function() {
			// Show success feedback
			const btn = document.getElementById('copy-filter-url-btn');
			const originalContent = btn.innerHTML;
			btn.innerHTML = '<i class="bi bi-check2"></i><span>Copied!</span>';
			btn.classList.add('bg-green-100', 'border-green-500', 'text-green-700');
			
			setTimeout(function() {
				btn.innerHTML = originalContent;
				btn.classList.remove('bg-green-100', 'border-green-500', 'text-green-700');
			}, 2000);
		}).catch(function(err) {
			// Fallback for browsers that don't support clipboard API
			alert('Failed to copy URL. Please copy manually: ' + fullUrl);
			console.error('Could not copy text: ', err);
		});
	});
</script>
@include('partials.filter-js')
@endsection
