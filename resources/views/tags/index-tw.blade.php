@extends('layouts.app-tw')

@section('title', 'Tags')

@section('content')

<div class="flex flex-col gap-6">

	
	<!-- Page Header -->
	<div>
		<h1 class="text-3xl font-bold text-primary mb-2">Tags</h1>
		<p class="text-muted-foreground">Browse genres and keyword tags.</p>
	</div>
	<!-- Add Tag Button -->
	<div class="mb-6 flex flex-wrap gap-2">
		@if ($signedIn)
		<a href="{!! URL::route('tags.create') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
			<i class="bi bi-plus-lg mr-2"></i>
			Add Tag
		</a>
		@endif
		<a href="{!! URL::route('pages.popular') !!}#popular-tags" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
			<i class="bi mr-2 bi-graph-up-arrow"></i>
			Popular
		</a>
	</div>


	<!-- Filters -->
	<x-filter-panel :action="url('/tags')" method="GET" :reset-url="url('/tags')" :has-filter="$hasFilter" :open="$hasFilter">
		<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
			<div class="min-w-0">
				<label for="filter_search" class="block text-sm font-medium text-muted-foreground mb-1">Search tags by name</label>
				<input type="text"
					name="search"
					id="filter_search"
					value="{{ $search ?? '' }}"
					placeholder="Tag name..."
					class="form-input-tw">
			</div>
			@auth
			<!-- Display Type Filter -->
			<div class="min-w-0">
				<label for="filter_display_type" class="block text-sm font-medium text-muted-foreground mb-1">Display Type</label>
				<select name="filters[display_type]" id="filter_display_type" class="form-select-tw">
					@foreach($displayTypes as $value => $label)
					<option value="{{ $value }}" {{ ($filters['display_type'] ?? 'all') === $value ? 'selected' : '' }}>{{ $label }}</option>
					@endforeach
				</select>
			</div>
			@endauth
		</div>
	</x-filter-panel>

	@include('tags.index-sort-pagination')

	<!-- Tags Grid -->
	<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
		@foreach ($tags as $tag)
			@include('tags.grid-card-tw')
		@endforeach
	</div>

	@include('tags.index-sort-pagination')
</div>

@stop

