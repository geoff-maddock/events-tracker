@extends('layouts.app-tw')

@section('title', 'Photos - Grid')

@section('content')

<div class="flex flex-col gap-6">
	<!-- Header & Actions -->
	<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
		<h1 class="text-2xl font-bold text-foreground flex items-center gap-2">
			Photos @include('photos.crumbs')
		</h1>
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
			<a href="{{ url()->action('PhotosController@rppReset') }}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg">
				Clear All <i class="bi bi-x ml-1"></i>
			</a>
		</div>
		@endif
	</div>

	<!-- Filter Panel -->
	<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6">
		{!! Form::open(['route' => [$filterRoute ?? 'photos.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

		<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
			<!-- Name Filter -->
			<div>
				<label for="filter_name" class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
				<input type="text"
					name="filters[name]"
					id="filter_name"
					value="{{ $filters['name'] ?? '' }}"
					class="form-input-tw"
					placeholder="Photo name...">
			</div>

			<!-- Is Primary Filter -->
			<div>
				<label for="filter_is_primary" class="block text-sm font-medium text-muted-foreground mb-1">Is Primary</label>
				<input type="text"
					name="filters[is_primary]"
					id="filter_is_primary"
					value="{{ $filters['is_primary'] ?? '' }}"
					class="form-input-tw"
					placeholder="0 or 1">
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

			<!-- Related Entity Filter -->
			<div>
				<label for="filter_related" class="block text-sm font-medium text-muted-foreground mb-1">Related Entity</label>
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

			<!-- Is Event Filter -->
			<div>
				<label for="filter_is_event" class="block text-sm font-medium text-muted-foreground mb-1">Is Event</label>
				<input type="text"
					name="filters[is_event]"
					id="filter_is_event"
					value="{{ $filters['is_event'] ?? '' }}"
					class="form-input-tw"
					placeholder="0 or 1">
			</div>
		</div>

		<!-- Filter Actions -->
		<div class="flex gap-2 mt-4">
			<button type="submit" class="px-4 py-2 bg-accent text-foreground border-2 border-primary rounded-lg hover:bg-accent/80 transition-colors">
				Apply
			</button>
			{!! Form::close() !!}
			{!! Form::open(['route' => ['photos.reset'], 'method' => 'GET']) !!}
			{!! Form::hidden('redirect', $redirect ?? 'photos.index') !!}
			{!! Form::hidden('key', $key ?? 'internal_photo_index') !!}
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
			@if(isset($photos))
			Showing {{ $photos->firstItem() ?? 0 }} to {{ $photos->lastItem() ?? 0 }} of {{ $photos->total() }} results
			@endif
		</div>

		<!-- Sort Controls & Pagination -->
		<div class="flex flex-wrap items-center gap-4">
			<form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
				<a href="{{ url()->action('PhotosController@rppReset') }}" class="px-3 py-2 bg-card border border-border rounded-lg hover:bg-accent transition-colors" title="Reset sort and filters">
					<i class="bi bi-arrow-clockwise"></i>
				</a>
				<select name="limit" class="form-select-tw text-sm py-1 auto-submit">
					@foreach($limitOptions as $value => $label)
					<option value="{{ $value }}" {{ ($limit ?? 24) == $value ? 'selected' : '' }}>{{ $label }}</option>
					@endforeach
				</select>
				<span class="text-muted-foreground text-sm">Sort by:</span>
				<select name="sort" class="form-select-tw text-sm py-1 auto-submit">
					@foreach($sortOptions as $value => $label)
					<option value="{{ $value }}" {{ ($sort ?? 'photos.created_at') == $value ? 'selected' : '' }}>{{ $label }}</option>
					@endforeach
				</select>
				<select name="direction" class="form-select-tw text-sm py-1 auto-submit">
					@foreach($directionOptions as $value => $label)
					<option value="{{ $value }}" {{ ($direction ?? 'desc') == $value ? 'selected' : '' }}>{{ $label }}</option>
					@endforeach
				</select>
			</form>
		</div>
	</div>

	<!-- Pagination (top) -->
	@if (isset($photos) && $photos->hasPages())
	<div class="mb-4">
		{!! $photos->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links('vendor.pagination.tailwind') !!}
	</div>
	@endif

	<!-- Grid Content -->
	@if (isset($photos) && count($photos) > 0)
		<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
			@foreach ($photos as $photo)
				@include('photos.cell-tw', ['photo' => $photo])
			@endforeach
		</div>

		<!-- Pagination (bottom) -->
		@if ($photos->hasPages())
		<div class="flex justify-center mt-6">
			{!! $photos->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links('vendor.pagination.tailwind') !!}
		</div>
		@endif
	@else
		<div class="text-center py-12 bg-card rounded-lg border border-border">
			<i class="bi bi-image text-4xl text-muted-foreground/50 mb-3 block"></i>
			<p class="text-muted-foreground">No photos found matching your criteria.</p>
		</div>
	@endif
</div>

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
