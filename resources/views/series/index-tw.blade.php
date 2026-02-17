@extends('layouts.app-tw')

@section('title', 'Series')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Series Listings @include('events.crumbs-tw')</h1>
	<p class="text-muted-foreground">Discover and explore event series.</p>
</div>

<!-- Action Menu -->
<div class="mb-6 flex flex-wrap gap-2">
	<a href="{!! URL::route('series.create') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
		<i class="bi bi-plus-lg mr-2"></i>
		Add Series
	</a>
	<a href="{!! URL::route('series.index') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Current Series
	</a>
	<a href="{!! URL::route('series.cancelled') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Cancelled Series
	</a>
	<a href="{!! URL::route('series.export') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm" target="_blank">
		<i class="bi bi-download mr-2"></i>
		Export TXT
	</a>
</div>

<!-- Filters Section -->
<div class="mb-6">
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
		@if(!empty($filters['occurrence_type']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Occurrence: {{ $occurrenceTypeOptions[$filters['occurrence_type']] ?? 'Unknown' }}
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
	</div>
	@endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6 overflow-hidden">
	{!! Form::open(['route' => [$filterRoute ?? 'series.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
		<!-- Name Filter -->
		<div class="min-w-0">
			<label for="filter_name" class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
			<input type="text"
				name="filters[name]"
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="Series name...">
		</div>

		<!-- Occurrence Type Filter -->
		<div class="min-w-0">
			<label for="filter_occurrence_type" class="block text-sm font-medium text-muted-foreground mb-1">Occurrence</label>
			{!! Form::select('filter_occurrence_type', $occurrenceTypeOptions, ($filters['occurrence_type'] ?? null),
			[
				'data-theme' => 'tailwind',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select occurrence',
				'name' => 'filters[occurrence_type]',
				'id' => 'filter_occurrence_type'
			])
			!!}
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
			{!! Form::select('filters[tag][]', $tagOptions, ($filters['tag'] ?? []),
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
	</div>

	<!-- Filter Actions -->
	<div class="flex gap-2 mt-4">
		<button type="submit" class="px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
			Apply
		</button>
		{!! Form::close() !!}
		{!! Form::open(['route' => ['series.reset'], 'method' => 'GET']) !!}
		<button type="submit" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
			Reset
		</button>
		{!! Form::close() !!}
	</div>
</div>

<!-- Series Grid -->
@if (isset($series) && count($series) > 0)
@include('series.index-sort-pagination')
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
	@foreach ($series as $s)
	@include('series.card-tw', ['series' => $s])
	@endforeach
</div>
<br>
@include('series.index-sort-pagination')
@else
<div class="text-center py-12">
	<i class="bi bi-collection-fill text-6xl text-muted-foreground/60 mb-4"></i>
	<p class="text-muted-foreground">No series found.</p>
</div>
@endif

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
</script>
@include('partials.filter-js')
@endsection
