@extends('layouts.app-tw')

@section('title', 'Series')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@if (isset($series) && count($series) > 0)
@section('page.json')
@include('series.index-json-ld')
@endsection
@endif

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

<!-- Filters -->
<x-filter-panel :action="route($filterRoute ?? 'series.filter')" :reset-url="route('series.reset')" :has-filter="$hasFilter">
	<x-slot:badges>
		@if(!empty($filters['name']))
		<x-filter-badge>Name: {{ $filters['name'] }}</x-filter-badge>
		@endif
		@if(!empty($filters['occurrence_type']))
		<x-filter-badge>Occurrence: {{ $occurrenceTypeOptions[$filters['occurrence_type']] ?? 'Unknown' }}</x-filter-badge>
		@endif
		@if(!empty($filters['venue']))
		<x-filter-badge>Venue: {{ $venueOptions[$filters['venue']] ?? 'Unknown' }}</x-filter-badge>
		@endif
		@if(!empty($filters['tag']))
		<x-filter-badge>Tag: {{ collect((array) $filters['tag'])->filter()->map(fn ($tag) => $tagOptions[$tag] ?? $tag)->implode(', ') }}</x-filter-badge>
		@endif
		@if(!empty($filters['related']))
		<x-filter-badge>Entity: {{ $relatedOptions[$filters['related']] ?? 'Unknown' }}</x-filter-badge>
		@endif
	</x-slot:badges>

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
			<x-ui.select-field name="filters[occurrence_type]" :options="$occurrenceTypeOptions" :selected="$filters['occurrence_type'] ?? null"
				data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select occurrence" id="filter_occurrence_type" />
		</div>

		<!-- Venue Filter -->
		<div class="min-w-0">
			<label for="filter_venue" class="block text-sm font-medium text-muted-foreground mb-1">Venue</label>
			<x-ui.select-field name="filters[venue]" :options="$venueOptions" :selected="$filters['venue'] ?? null"
				data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select a venue" id="filter_venue" />
		</div>

		<!-- Tag Filter -->
		<div class="min-w-0">
			<label for="filter_tag" class="block text-sm font-medium text-muted-foreground mb-1">Tag</label>
			<x-ui.select-field name="filters[tag]" :options="$tagOptions" :selected="$filters['tag'] ?? null"
				data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select a tag" id="filter_tag" />
		</div>

		<!-- Related Entity Filter -->
		<div class="min-w-0">
			<label for="filter_related" class="block text-sm font-medium text-muted-foreground mb-1">Related Entity</label>
			<x-ui.select-field name="filters[related]" :options="$relatedOptions" :selected="$filters['related'] ?? null"
				data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select an entity" id="filter_related" />
		</div>
	</div>

	<x-slot:actions>
		@if($hasFilter)
		<button type="button" id="copy-filter-url-btn" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors flex items-center gap-2">
			<i class="bi bi-link-45deg"></i>
			<span>Copy Filter URL</span>
		</button>
		@endif
	</x-slot:actions>
</x-filter-panel>

<!-- Series Grid -->
@if (isset($series) && count($series) > 0)
<x-list-toolbar :paginator="$series ?? null" :limit="$limit ?? 10" :sort="$sort ?? 'series.name'" :direction="$direction ?? 'asc'"
	:limit-options="$limitOptions" :sort-options="$sortOptions" :direction-options="$directionOptions" />
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
	@foreach ($series as $s)
	@include('series.card-tw', ['series' => $s])
	@endforeach
</div>
<br>
<x-list-toolbar :paginator="$series ?? null" :limit="$limit ?? 10" :sort="$sort ?? 'series.name'" :direction="$direction ?? 'asc'"
	:limit-options="$limitOptions" :sort-options="$sortOptions" :direction-options="$directionOptions" />
@else
<x-empty-state icon="bi-collection-fill">No series found.</x-empty-state>
@endif

@stop

@section('footer')
<script>
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

		const baseUrl = '{{ Route::has('series.applyFilterFromUrl') ? route('series.applyFilterFromUrl') : route('series.filter') }}';
		const fullUrl = `${baseUrl}?${params.toString()}`;

		const btn = document.getElementById('copy-filter-url-btn');

		function showCopiedFeedback() {
			const originalContent = btn.innerHTML;
			btn.innerHTML = '<i class="bi bi-check2"></i><span>Copied!</span>';
			btn.classList.add('bg-green-100', 'border-green-500', 'text-green-700');
			setTimeout(function() {
				btn.innerHTML = originalContent;
				btn.classList.remove('bg-green-100', 'border-green-500', 'text-green-700');
			}, 2000);
		}

		function copyToClipboard(url) {
			navigator.clipboard.writeText(url).then(showCopiedFeedback).catch(function() {
				alert('Failed to copy URL. Please copy manually: ' + url);
			});
		}

		// Shorten the URL before copying
		const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
		fetch('{{ route('short-url.shorten') }}', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': csrfToken,
				'Accept': 'application/json',
			},
			body: JSON.stringify({ url: fullUrl }),
		})
		.then(function(response) { return response.json(); })
		.then(function(data) { copyToClipboard(data.short_url || fullUrl); })
		.catch(function() { copyToClipboard(fullUrl); });
	});
</script>
@endsection
