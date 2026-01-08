@extends('layouts.app-tw')

@section('title')
Entities @include('entities.title-crumbs')
@endsection

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
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
	<button id="filters-toggle-btn" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border-2 border-primary rounded-lg hover:bg-accent/80 transition-colors">
		<i class="bi bi-funnel mr-2"></i>
		<span id="filters-toggle-text">@if($hasFilter) Hide @else Show @endif Filters</span>
		<i class="bi bi-chevron-down ml-2 transition-transform @if($hasFilter) rotate-180 @endif" id="filters-chevron"></i>
	</button>
	
	<!-- Active Filters / Reset -->
	@if($hasFilter)
	<div class="inline-flex items-center gap-2 ml-4">
		<a href="{{ url()->action('EntitiesController@rppReset') }}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg">
			Clear All <i class="bi bi-x ml-1"></i>
		</a>
	</div>
	@endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6">
	{!! Form::open(['route' => ['entities.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
	
	<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
		<!-- Name Filter -->
		<div>
			<label for="filter_name" class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
			<input type="text"
				name="filters[name]"
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="Entity name...">
		</div>

		<!-- Role Filter -->
		<div>
			<label for="filter_role" class="block text-sm font-medium text-muted-foreground mb-1">Role</label>
			{!! Form::select('filter_role', $roleOptions, ($filters['role'] ?? null),
			[
				'data-theme' => 'bootstrap-5',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a role',
				'name' => 'filters[role]',
				'id' => 'filter_role'
			])
			!!}
		</div>

		<!-- Tag Filter -->
		<div>
			<label for="filter_tag" class="block text-sm font-medium text-muted-foreground mb-1">Tag</label>
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

		<!-- Entity Type Filter -->
		<div>
			<label for="filter_entity_type" class="block text-sm font-medium text-muted-foreground mb-1">Type</label>
			{!! Form::select('filter_entity_type', $entityTypeOptions, ($filters['entity_type'] ?? null),
			[
				'data-theme' => 'bootstrap-5',
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a type',
				'name' => 'filters[entity_type]',
				'id' => 'filter_entity_type'
			])
			!!}
		</div>

		<!-- Entity Status Filter -->
		<div>
			<label for="filter_entity_status" class="block text-sm font-medium text-muted-foreground mb-1">Status</label>
			{!! Form::select('filter_entity_status', $entityStatusOptions, ($filters['entity_status'] ?? null),
			[
				'data-theme' => 'bootstrap-5',
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
		<button type="submit" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border-2 border-primary rounded-lg hover:bg-accent/80 transition-colors">
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
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
	<div class="text-sm text-muted-foreground">
		@if(isset($entities))
		Showing {{ $entities->firstItem() ?? 0 }} - {{ $entities->lastItem() ?? 0 }} of {{ $entities->total() }} entities
		@endif
	</div>

	<div class="flex flex-wrap items-center gap-2">
		<a href="{{ url()->action('EntitiesController@rppReset') }}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-accent transition-colors" title="Reset">
			<i class="bi bi-arrow-clockwise"></i>
		</a>

		<form action="" method="GET" class="flex gap-2">
			<select name="limit" class="form-select-tw text-sm auto-submit">
				@foreach($limitOptions as $key => $value)
				<option value="{{ $key }}" {{ ($limit ?? 10) == $key ? 'selected' : '' }}>{{ $value }}</option>
				@endforeach
			</select>

			<select name="sort" class="form-select-tw text-sm auto-submit">
				@foreach($sortOptions as $key => $value)
				<option value="{{ $key }}" {{ ($sort ?? 'entities.name') == $key ? 'selected' : '' }}>{{ $value }}</option>
				@endforeach
			</select>

			<select name="direction" class="form-select-tw text-sm auto-submit">
				@foreach($directionOptions as $key => $value)
				<option value="{{ $key }}" {{ ($direction ?? 'asc') == $key ? 'selected' : '' }}>{{ $value }}</option>
				@endforeach
			</select>
		</form>

		<!-- Pagination Controls -->
		@if(isset($entities) && $entities->hasPages())
		<div class="flex items-center gap-1 ml-2">
			<span class="text-muted-foreground mr-1 hidden md:inline">|</span>
			@if ($entities->onFirstPage())
				<span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-muted-foreground/50 bg-card border border-border cursor-not-allowed rounded-lg">
					<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
					</svg>
				</span>
			@else
				<a href="{{ $entities->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-muted-foreground bg-card border border-border rounded-lg hover:bg-accent transition-colors">
					<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
					</svg>
				</a>
			@endif

			<span class="px-2 py-1 text-sm text-foreground">
				{{ $entities->currentPage() }}
			</span>

			@if ($entities->hasMorePages())
				<a href="{{ $entities->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-muted-foreground bg-card border border-border rounded-lg hover:bg-accent transition-colors">
					<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
					</svg>
				</a>
			@else
				<span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-muted-foreground/50 bg-card border border-border cursor-not-allowed rounded-lg">
					<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
						<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
					</svg>
				</span>
			@endif
		</div>
		@endif
	</div>
</div>

<!-- Recently Popular Entities -->
@if (isset($latestEntities) && count($latestEntities) > 0)
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
@endif

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
	
	if (toggleBtn) {
		toggleBtn.addEventListener('click', function() {
			filterPanel.classList.toggle('hidden');
			const isHidden = filterPanel.classList.contains('hidden');
			toggleText.textContent = isHidden ? 'Show Filters' : 'Hide Filters';
			chevron.classList.toggle('rotate-180');
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
