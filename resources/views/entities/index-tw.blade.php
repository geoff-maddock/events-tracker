@extends('layouts.app-tw')

@section('title')
Entities @include('entities.title-crumbs')
@endsection

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Entity Listings</h1>
	<p class="text-muted-foreground">Discover venues, artists, promoters, and more.</p>
</div>

<!-- Action Menu -->
<div class="mb-6 flex flex-wrap gap-2">
	<a href="{!! URL::route('entities.create') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
		<i class="bi bi-plus-lg mr-2"></i>
		Add Entity
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'artist']) !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Artists
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'band']) !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Bands
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'dj']) !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		DJs
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'producer']) !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Producers
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'promoter']) !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Promoters
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'shop']) !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Shops
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'venue']) !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Venues
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
		@if(!empty($filters['role']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Role: {{ $roleOptions[$filters['role']] ?? 'Unknown' }}
		</span>
		@endif
		@if(!empty($filters['tag']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Tag: {{ $tagOptions[$filters['tag']] ?? 'Unknown' }}
		</span>
		@endif
		@if(!empty($filters['entity_type']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Type: {{ $entityTypeOptions[$filters['entity_type']] ?? 'Unknown' }}
		</span>
		@endif
		@if(!empty($filters['entity_status']))
		<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
			Status: {{ $entityStatusOptions[$filters['entity_status']] ?? 'Unknown' }}
		</span>
		@endif
	</div>
	@endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6 overflow-hidden">
	{!! Form::open(['route' => ['entities.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
		<!-- Name Filter -->
		<div class="min-w-0">
			<label for="filter_name" class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
			<input type="text"
				name="filters[name]"
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="Entity name...">
		</div>

		<!-- Role Filter -->
		<div class="min-w-0">
			<label for="filter_role" class="block text-sm font-medium text-muted-foreground mb-1">Role</label>
			{!! Form::select('filter_role', $roleOptions, ($filters['role'] ?? null),
			[
				'data-theme' => 'tailwind',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a role',
				'name' => 'filters[role]',
				'id' => 'filter_role'
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

		<!-- Entity Type Filter -->
		<div class="min-w-0">
			<label for="filter_entity_type" class="block text-sm font-medium text-muted-foreground mb-1">Type</label>
			{!! Form::select('filter_entity_type', $entityTypeOptions, ($filters['entity_type'] ?? null),
			[
				'data-theme' => 'tailwind',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a type',
				'name' => 'filters[entity_type]',
				'id' => 'filter_entity_type'
			])
			!!}
		</div>

		<!-- Entity Status Filter -->
		<div class="min-w-0">
			<label for="filter_entity_status" class="block text-sm font-medium text-muted-foreground mb-1">Status</label>
			{!! Form::select('filter_entity_status', $entityStatusOptions, ($filters['entity_status'] ?? null),
			[
				'data-theme' => 'tailwind',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a status',
				'name' => 'filters[entity_status]',
				'id' => 'filter_entity_status'
			])
			!!}
		</div>
	</div>

	<!-- Filter Actions -->
	<div class="mt-4 flex gap-2">
		<button type="submit" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
			<i class="bi bi-check-lg mr-2"></i>
			Apply Filters
		</button>
		{!! Form::close() !!}
		{!! Form::open(['route' => ['entities.reset'], 'method' => 'GET']) !!}
		<button type="submit" class="inline-flex items-center px-4 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-accent transition-colors">
			<i class="bi bi-arrow-clockwise mr-2"></i>
			Reset
		</button>
		{!! Form::close() !!}
	</div>
</div>

<!-- List Controls -->
<div class="flex flex-wrap items-center gap-4 mb-6">
	<!-- Results Count -->
	<div class="text-sm text-muted-foreground w-full sm:w-auto">
		@if(isset($entities))
		Showing {{ $entities->firstItem() ?? 0 }} to {{ $entities->lastItem() ?? 0 }} of {{ $entities->total() }} results
		@endif
	</div>

	<!-- Sort Controls -->
	<div class="flex items-center justify-center gap-4 w-full sm:flex-1">
		<form action="{{ url()->current() }}" method="GET" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
			<select name="limit" class="form-select-tw text-sm py-1.5 px-3 auto-submit flex-1 sm:flex-initial sm:max-w-[120px] min-w-0">
				@foreach($limitOptions as $value => $label)
				<option value="{{ $value }}" {{ ($limit ?? 10) == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<span class="text-muted-foreground text-sm hidden sm:inline">Sort by:</span>
			<select name="sort" class="form-select-tw text-sm py-1.5 px-3 auto-submit flex-1 sm:flex-initial sm:max-w-[160px] min-w-0">
				@foreach($sortOptions as $value => $label)
				<option value="{{ $value }}" {{ ($sort ?? 'entities.name') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<select name="direction" class="form-select-tw text-sm py-1.5 px-3 auto-submit flex-1 sm:flex-initial sm:max-w-[140px] min-w-0">
				@foreach($directionOptions as $value => $label)
				<option value="{{ $value }}" {{ ($direction ?? 'asc') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
		</form>
	</div>

	<!-- Pagination (top) -->
	@if(isset($entities) && $entities->hasPages())
	<div class="flex items-center justify-center sm:justify-end gap-1 w-full sm:w-auto">
		@foreach($entities->getUrlRange(max(1, $entities->currentPage() - 2), min($entities->lastPage(), $entities->currentPage() + 2)) as $page => $url)
		<a href="{{ $url }}" class="px-2 sm:px-3 py-1 rounded {{ $page == $entities->currentPage() ? 'bg-accent text-foreground border border-primary' : 'text-muted-foreground hover:bg-card' }}">{{ $page }}</a>
		@endforeach
	</div>
	@endif
</div>

<!-- Recently Popular Entities -->
{{-- @if (isset($latestEntities) && count($latestEntities) > 0)
<div class="mb-8">
	<div class="rounded-lg border border-border bg-card shadow">
		<div class="bg-primary px-6 py-3 flex items-center justify-between rounded-t-lg">
			<h2 class="text-lg font-semibold text-primary-foreground">Recently Popular Entities</h2>
			<button type="button" class="text-primary-foreground hover:text-foreground transition-colors"
				onclick="document.getElementById('entity-popular').classList.toggle('hidden')"
				title="Show / Hide">
				<i class="bi bi-eye-fill"></i>
			</button>
		</div>
		<div id="entity-popular" class="p-6">
			@include('entities.grid-tw', ['entities' => $latestEntities])
		</div>
	</div>
</div>
@endif --}}

<!-- Main Entity List -->
<div class="mb-6">
	@include('entities.grid-tw', ['entities' => $entities])
</div>

@stop

@section('footer')
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Filter toggle
	const toggleBtn = document.getElementById('filters-toggle-btn');
	const filterPanel = document.getElementById('filter-panel');
	const toggleText = document.getElementById('filters-toggle-text');
	const chevron = document.getElementById('filters-chevron');
	const badges = document.getElementById('active-filters-badges');
	
	if (toggleBtn) {
		toggleBtn.addEventListener('click', function() {
			filterPanel.classList.toggle('hidden');
			const isHidden = filterPanel.classList.contains('hidden');
			toggleText.textContent = isHidden ? 'Show Filters' : 'Hide Filters';
			chevron.classList.toggle('rotate-180');
			
			// Show badges when filters are hidden, hide when open
			if (badges) {
				if (isHidden) {
					badges.classList.remove('hidden');
				} else {
					badges.classList.add('hidden');
				}
			}
		});
	}
	
	// Auto-submit on select change
	const autoSubmitSelects = document.querySelectorAll('.auto-submit');
	autoSubmitSelects.forEach(select => {
		select.addEventListener('change', function() {
			this.form.submit();
		});
	});
});
</script>
@include('partials.filter-js')
@endsection
