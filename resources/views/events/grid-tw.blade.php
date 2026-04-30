@extends('layouts.app-tw')

@section('title', 'Events - Grid')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Event Grid @include('events.crumbs-tw')</h1>
	<p class="text-muted-foreground">Browse upcoming events in a compact grid layout.</p>
</div>

<!-- Action Buttons -->
<div class="mb-6 flex flex-wrap gap-2 items-center">
	<a href="{!! URL::route('events.create') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
		<i class="bi bi-plus-lg mr-2"></i>
		Add Event
	</a>
	<div class="ml-auto relative" id="actions-menu-container">
		<button id="actions-menu-btn" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-accent transition-colors text-sm" aria-haspopup="true" aria-expanded="false">
			<i class="bi bi-three-dots"></i>
		</button>
		<div id="actions-menu-dropdown" class="hidden absolute right-0 mt-1 w-44 bg-card border border-border rounded-lg shadow-lg z-10">
			<a href="{!! URL::route('events.export') !!}" class="flex items-center px-4 py-2 text-sm text-muted-foreground hover:bg-accent rounded-t-lg transition-colors" target="_blank">
				<i class="bi bi-download mr-2"></i>
				Export TXT
			</a>
			<a href="{!! URL::route('events.indexIcal') !!}" class="flex items-center px-4 py-2 text-sm text-muted-foreground hover:bg-accent rounded-b-lg transition-colors" target="_blank">
				<i class="bi bi-calendar-event mr-2"></i>
				Export iCal
			</a>
		</div>
	</div>
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
	<div id="active-filters-badges" class="@if($hasFilter) hidden @endif inline-flex flex-wrap items-center gap-2 ml-4">
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
		@if(!empty($filters['my_events']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			My Events
		</span>
		@endif
		@if(!empty($filters['display_type']) && $filters['display_type'] !== 'all')
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			{{ ['attending' => 'Events Attending', 'not_attending' => 'Events Not Attending', 'created' => 'Events Created', 'not_created' => 'Events Not Created'][$filters['display_type']] ?? $filters['display_type'] }}
		</span>
		@endif
	</div>
	@endif

	@if($hasFilter)
	<a id="filters-reset-closed" href="{{ route('events.reset') }}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg @if($hasFilter) hidden @endif">
		Reset <i class="bi bi-x ml-1"></i>
	</a>
	@endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6 overflow-hidden">
	{!! Form::open(['route' => ['events.grid'], 'name' => 'filters', 'method' => 'POST']) !!}

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
			{!! Form::select('filters[tag][]', array_filter($tagOptions, fn($key) => $key !== '', ARRAY_FILTER_USE_KEY), ($filters['tag'] ?? null),
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
			<label for="filter_event_type" class="block text-sm font-medium text-muted-foreground mb-1">Event Type</label>
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

		@auth
		<!-- Display Type Filter -->
		<div class="min-w-0">
			<label for="filter_display_type" class="block text-sm font-medium text-muted-foreground mb-1">Display Type</label>
			<select name="filters[display_type]" id="filter_display_type" class="form-select-tw">
				<option value="all" {{ ($filters['display_type'] ?? 'all') === 'all' ? 'selected' : '' }}>All Events</option>
				<option value="attending" {{ ($filters['display_type'] ?? '') === 'attending' ? 'selected' : '' }}>Events Attending</option>
				<option value="not_attending" {{ ($filters['display_type'] ?? '') === 'not_attending' ? 'selected' : '' }}>Events Not Attending</option>
				<option value="created" {{ ($filters['display_type'] ?? '') === 'created' ? 'selected' : '' }}>Events Created</option>
				<option value="not_created" {{ ($filters['display_type'] ?? '') === 'not_created' ? 'selected' : '' }}>Events Not Created</option>
			</select>
		</div>
		@endauth
	</div>

	<!-- Filter Actions -->
	<div class="flex gap-2 mt-4">
		<button type="submit" class="px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
			Apply
		</button>
		{!! Form::close() !!}
		{!! Form::open(['route' => ['events.reset'], 'method' => 'GET']) !!}
		{!! Form::hidden('redirect', 'events.grid') !!}
		{!! Form::hidden('key', 'internal_event_grid') !!}
		<button id="filters-reset-open" type="submit" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors @if(!$hasFilter) hidden @endif">
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



<!-- Grid Content -->
@if (isset($events) && count($events) > 0)
@include('events.index-sort-pagination')
	<div class="grid gap-4 w-full" style="grid-template-columns: repeat(auto-fill, minmax(max(120px, calc((100% - 15 * 1rem) / 16)), 1fr));">
		@php $lastDate = ''; @endphp
		@foreach ($events as $event)
			@php
				$currentDate = $event->start_at->format('Y-m-d');
				$showDateBar = $currentDate !== $lastDate;
				if ($showDateBar) {
					$lastDate = $currentDate;
				}
				$isWeekend = $event->start_at->isWeekend() || $event->start_at->isFriday();
				$dateLabel = $event->start_at->format('D, M j, Y');
			@endphp
			@include('events.cell-compact-tw', [
				'event' => $event,
				'showDateBar' => $showDateBar,
				'dateLabel' => $dateLabel,
				'isWeekend' => $isWeekend
			])
		@endforeach
	</div>

	<br>
@include('events.index-sort-pagination')
@else
	<div class="text-center py-12 bg-card rounded-lg border border-border">
		<i class="bi bi-calendar-x text-4xl text-muted-foreground/50 mb-3 block"></i>
		<p class="text-muted-foreground">No events found matching your criteria.</p>
	</div>
@endif

@stop

@section('footer')
<script>
    // Actions menu toggle
    (function() {
        const btn = document.getElementById('actions-menu-btn');
        const dropdown = document.getElementById('actions-menu-dropdown');
        if (!btn || !dropdown) return;

        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const open = !dropdown.classList.contains('hidden');
            dropdown.classList.toggle('hidden', open);
            btn.setAttribute('aria-expanded', String(!open));
        });

        document.addEventListener('click', function() {
            dropdown.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        });
    })();

    // Filter toggle functionality
    document.getElementById('filters-toggle-btn')?.addEventListener('click', function() {
        const panel = document.getElementById('filter-panel');
        const badges = document.getElementById('active-filters-badges');
        const text = document.getElementById('filters-toggle-text');
        const chevron = document.getElementById('filters-chevron');
        const resetClosed = document.getElementById('filters-reset-closed');
        const resetOpen = document.getElementById('filters-reset-open');
        const hasFilter = @json($hasFilter);

        panel.classList.toggle('hidden');

        if (panel.classList.contains('hidden')) {
            text.textContent = 'Show Filters';
            chevron.classList.remove('rotate-180');
            // Show badges when filters are hidden
            if (badges) {
                badges.classList.remove('hidden');
            }
            if (resetClosed && hasFilter) resetClosed.classList.remove('hidden');
            if (resetOpen) resetOpen.classList.add('hidden');
        } else {
            text.textContent = 'Hide Filters';
            chevron.classList.add('rotate-180');
            // Hide badges when filters are shown
            if (badges) {
                badges.classList.add('hidden');
            }
            if (resetClosed) resetClosed.classList.add('hidden');
            if (resetOpen && hasFilter) resetOpen.classList.remove('hidden');
        }
    });

	// Copy filter URL functionality
	document.getElementById('copy-filter-url-btn')?.addEventListener('click', function() {
		const filters = @json($filters ?? []);
		const sort = @json($sort ?? '');
		const direction = @json($direction ?? '');
		const limit = @json($limit ?? null);

		const params = new URLSearchParams();

		if (filters && Object.keys(filters).length > 0) {
			for (const [key, value] of Object.entries(filters)) {
				if (value !== null && value !== '' && value !== undefined) {
					if (Array.isArray(value)) {
						value.forEach(v => {
							if (v !== null && v !== '') {
								params.append(`filters[${key}][]`, v);
							}
						});
					} else if (typeof value === 'object' && value !== null) {
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

		if (sort) params.append('sort', sort);
		if (direction) params.append('direction', direction);
		if (limit) params.append('limit', limit);

		const baseUrl = '{{ route('events.grid.applyFilterFromUrl') }}';
		const fullUrl = `${baseUrl}?${params.toString()}`;

		navigator.clipboard.writeText(fullUrl).then(function() {
			const btn = document.getElementById('copy-filter-url-btn');
			const originalContent = btn.innerHTML;
			btn.innerHTML = '<i class="bi bi-check2"></i><span>Copied!</span>';
			btn.classList.add('bg-green-100', 'border-green-500', 'text-green-700');

			setTimeout(function() {
				btn.innerHTML = originalContent;
				btn.classList.remove('bg-green-100', 'border-green-500', 'text-green-700');
			}, 2000);
		}).catch(function(err) {
			alert('Failed to copy URL. Please copy manually: ' + fullUrl);
			console.error('Could not copy text: ', err);
		});
	});
</script>
@include('partials.filter-js')
@endsection
