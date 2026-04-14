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
		<a href="{!! URL::route('pages.popular') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
			<i class="bi mr-2 bi-graph-up-arrow"></i>
			Popular
		</a>
	</div>


	<!-- Filters Section -->
	<div class="mb-6 flex flex-wrap items-start gap-2">
		<button id="filters-toggle-btn" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
			<i class="bi bi-funnel mr-2"></i>
			<span id="filters-toggle-text">@if($hasFilter) Hide @else Show @endif Filters</span>
			<i class="bi bi-chevron-down ml-2 transition-transform @if($hasFilter) rotate-180 @endif" id="filters-chevron"></i>
		</button>
		@if($hasFilter)
		<a id="filters-reset-closed" href="{{ url('/tags') }}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg @if($hasFilter) hidden @endif">
			Reset <i class="bi bi-x ml-1"></i>
		</a>
		@endif
	</div>

	<!-- Filter Panel -->
	<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6 overflow-hidden">
		<form method="GET" action="{{ url('/tags') }}" class="flex flex-col gap-4">
			<div>
				<label for="filter_search" class="block text-sm font-medium text-muted-foreground mb-1">Search tags by name</label>
				<input type="text"
					name="search"
					id="filter_search"
					value="{{ $search ?? '' }}"
					placeholder="Tag name..."
					class="form-input-tw">
			</div>
			<div class="flex gap-2">
				<button type="submit" class="px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
					Apply
				</button>
				<a id="filters-reset-open" href="{{ url('/tags') }}" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors @if(!$hasFilter) hidden @endif">
					Reset
				</a>
			</div>
		</form>
	</div>

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

@section('footer')
<script>
	// Filter toggle functionality
	document.getElementById('filters-toggle-btn')?.addEventListener('click', function() {
		const panel = document.getElementById('filter-panel');
		const text = document.getElementById('filters-toggle-text');
		const chevron = document.getElementById('filters-chevron');
		const resetClosed = document.getElementById('filters-reset-closed');
		const resetOpen = document.getElementById('filters-reset-open');
		const hasFilter = @json($hasFilter);

		panel.classList.toggle('hidden');

		if (panel.classList.contains('hidden')) {
			text.textContent = 'Show Filters';
			chevron.classList.remove('rotate-180');
			if (resetClosed && hasFilter) resetClosed.classList.remove('hidden');
			if (resetOpen) resetOpen.classList.add('hidden');
		} else {
			text.textContent = 'Hide Filters';
			chevron.classList.add('rotate-180');
			if (resetClosed) resetClosed.classList.add('hidden');
			if (resetOpen && hasFilter) resetOpen.classList.remove('hidden');
		}
	});
</script>
@endsection
