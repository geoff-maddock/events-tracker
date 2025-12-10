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
	<p class="text-gray-400">Discover venues, artists, promoters, and more.</p>
</div>

<!-- Action Menu -->
<div class="mb-6 flex flex-wrap gap-2">
	<a href="{!! URL::route('entities.create') !!}" class="inline-flex items-center px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors">
		<i class="bi bi-plus-lg mr-2"></i>
		Add Entity
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'artist']) !!}" class="inline-flex items-center px-3 py-2 bg-dark-surface border border-dark-border text-gray-300 rounded-lg hover:bg-dark-card transition-colors text-sm">
		Artists
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'band']) !!}" class="inline-flex items-center px-3 py-2 bg-dark-surface border border-dark-border text-gray-300 rounded-lg hover:bg-dark-card transition-colors text-sm">
		Bands
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'dj']) !!}" class="inline-flex items-center px-3 py-2 bg-dark-surface border border-dark-border text-gray-300 rounded-lg hover:bg-dark-card transition-colors text-sm">
		DJs
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'producer']) !!}" class="inline-flex items-center px-3 py-2 bg-dark-surface border border-dark-border text-gray-300 rounded-lg hover:bg-dark-card transition-colors text-sm">
		Producers
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'promoter']) !!}" class="inline-flex items-center px-3 py-2 bg-dark-surface border border-dark-border text-gray-300 rounded-lg hover:bg-dark-card transition-colors text-sm">
		Promoters
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'shop']) !!}" class="inline-flex items-center px-3 py-2 bg-dark-surface border border-dark-border text-gray-300 rounded-lg hover:bg-dark-card transition-colors text-sm">
		Shops
	</a>
	<a href="{!! URL::route('entities.role', ['role' => 'venue']) !!}" class="inline-flex items-center px-3 py-2 bg-dark-surface border border-dark-border text-gray-300 rounded-lg hover:bg-dark-card transition-colors text-sm">
		Venues
	</a>
</div>

<!-- Filters Section -->
<div class="mb-6">
	<button id="filters-toggle-btn" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
		<i class="bi bi-funnel mr-2"></i>
		<span id="filters-toggle-text">@if($hasFilter) Hide @else Show @endif Filters</span>
		<i class="bi bi-chevron-down ml-2 transition-transform @if($hasFilter) rotate-180 @endif" id="filters-chevron"></i>
	</button>
	
	<!-- Active Filters / Reset -->
	@if($hasFilter)
	<div class="inline-flex items-center gap-2 ml-4">
		<a href="{{ url()->action('EntitiesController@rppReset') }}" class="inline-flex items-center px-3 py-1 text-sm text-gray-300 hover:text-white border border-dark-border rounded-lg">
			Clear All <i class="bi bi-x ml-1"></i>
		</a>
	</div>
	@endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-dark-surface border border-dark-border rounded-lg p-4 mb-6">
	{!! Form::open(['route' => ['entities.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
	
	<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
		<!-- Name Filter -->
		<div>
			<label for="filter_name" class="block text-sm font-medium text-gray-300 mb-1">Name</label>
			<input type="text" 
				name="filters[name]" 
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="Entity name...">
		</div>

		<!-- Role Filter -->
		<div>
			<label for="filter_role" class="block text-sm font-medium text-gray-300 mb-1">Role</label>
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

		<!-- Entity Type Filter -->
		<div>
			<label for="filter_entity_type" class="block text-sm font-medium text-gray-300 mb-1">Type</label>
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
			<label for="filter_entity_status" class="block text-sm font-medium text-gray-300 mb-1">Status</label>
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
		<button type="submit" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
			<i class="bi bi-check-lg mr-2"></i>
			Apply Filters
		</button>
		{!! Form::close() !!}
		{!! Form::open(['route' => ['entities.reset'], 'method' => 'GET']) !!}
		<button type="submit" class="inline-flex items-center px-4 py-2 bg-dark-card border border-dark-border text-gray-300 rounded-lg hover:bg-dark-border transition-colors">
			<i class="bi bi-arrow-clockwise mr-2"></i>
			Reset
		</button>
		{!! Form::close() !!}
	</div>
</div>

<!-- List Controls -->
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
	<div class="text-sm text-gray-400">
		@if(isset($entities))
		Showing {{ $entities->firstItem() ?? 0 }} - {{ $entities->lastItem() ?? 0 }} of {{ $entities->total() }} entities
		@endif
	</div>
	
	<div class="flex gap-2">
		<a href="{{ url()->action('EntitiesController@rppReset') }}" class="inline-flex items-center px-3 py-2 bg-dark-card border border-dark-border text-gray-300 rounded-lg hover:bg-dark-border transition-colors" title="Reset">
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
	</div>
</div>

<!-- Recently Popular Entities -->
@if (isset($latestEntities) && count($latestEntities) > 0)
<div class="mb-8">
	<div class="rounded-lg border border-dark-border bg-card shadow">
		<div class="bg-primary px-6 py-3 flex items-center justify-between rounded-t-lg">
			<h2 class="text-lg font-semibold text-white">Recently Popular Entities</h2>
			<button type="button" class="text-white hover:text-gray-200 transition-colors" 
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

<!-- Pagination Top -->
@if(isset($entities))
<div class="mb-6">
	{!! $entities->onEachSide(2)->links() !!}
</div>
@endif

<!-- Main Entity List -->
<div class="mb-6">
	@include('entities.grid-tw', ['entities' => $entities])
</div>

<!-- Pagination Bottom -->
@if(isset($entities))
<div class="mb-6">
	{!! $entities->onEachSide(2)->links() !!}
</div>
@endif

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
