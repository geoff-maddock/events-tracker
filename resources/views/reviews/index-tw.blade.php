@extends('layouts.app-tw')

@section('title','Reviews')

@section('content')

<div class="flex flex-col gap-6">
	<!-- Header -->
	<div>
		<h1 class="text-2xl font-bold text-foreground">Reviews @include('reviews.crumbs')</h1>
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
			<a href="{{ url()->action('ReviewsController@rppReset') }}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg">
				Clear All <i class="bi bi-x ml-1"></i>
			</a>
		</div>
		@endif
	</div>

	<!-- Filter Panel -->
	<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6">
		{!! Form::open(['route' => [$filterRoute ?? 'reviews.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

		<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
			<!-- Review Filter -->
			<div>
				<label for="filter_review" class="block text-sm font-medium text-muted-foreground mb-1">Review</label>
				<input type="text"
					name="filters[review]"
					id="filter_review"
					value="{{ $filters['review'] ?? '' }}"
					class="form-input-tw"
					placeholder="Search reviews...">
			</div>

			<!-- User Filter -->
			<div>
				<label for="filter_user" class="block text-sm font-medium text-muted-foreground mb-1">User</label>
				{!! Form::select('filter_user', $userOptions, ($filters['user'] ?? null),
				[
					'data-theme' => 'bootstrap-5',
					'class' => 'form-select-tw select2',
					'data-placeholder' => 'Select a user',
					'name' => 'filters[user]',
					'id' => 'filter_user'
				])
				!!}
			</div>

			<!-- Review Type Filter -->
			<div>
				<label for="filter_review_type" class="block text-sm font-medium text-muted-foreground mb-1">Type</label>
				{!! Form::select('filter_review_type', $reviewTypeOptions, ($filters['review_type'] ?? null),
				[
					'data-theme' => 'bootstrap-5',
					'class' => 'form-select-tw select2',
					'data-placeholder' => 'Select a type',
					'name' => 'filters[review_type]',
					'id' => 'filter_review_type'
				])
				!!}
			</div>
		</div>

		<!-- Filter Actions -->
		<div class="flex gap-2 mt-4">
			<button type="submit" class="px-4 py-2 bg-accent text-foreground border-2 border-primary rounded-lg hover:bg-accent/80 transition-colors">
				Apply
			</button>
			{!! Form::close() !!}
			{!! Form::open(['route' => ['reviews.reset'], 'method' => 'GET']) !!}
			<button type="submit" class="px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
				Reset
			</button>
			{!! Form::close() !!}
		</div>
	</div>

	<!-- Sort Controls -->
	<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
		<div class="text-sm text-muted-foreground">
			@if(isset($reviews))
			Showing {{ $reviews->firstItem() ?? 0 }} to {{ $reviews->lastItem() ?? 0 }} of {{ $reviews->total() }} results
			@endif
		</div>

		<div class="flex flex-wrap items-center gap-4">
			<form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
				<a href="{{ url()->action('ReviewsController@rppReset') }}" class="px-3 py-2 bg-card border border-border rounded-lg hover:bg-accent transition-colors" title="Reset sort and filters">
					<i class="bi bi-arrow-clockwise"></i>
				</a>
				<select name="limit" class="form-select-tw text-sm py-1 auto-submit">
					@foreach($limitOptions as $value => $label)
					<option value="{{ $value }}" {{ ($limit ?? 10) == $value ? 'selected' : '' }}>{{ $label }}</option>
					@endforeach
				</select>
				<span class="text-muted-foreground text-sm">Sort by:</span>
				<select name="sort" class="form-select-tw text-sm py-1 auto-submit">
					@foreach($sortOptions as $value => $label)
					<option value="{{ $value }}" {{ ($sort ?? 'name') == $value ? 'selected' : '' }}>{{ $label }}</option>
					@endforeach
				</select>
				<select name="direction" class="form-select-tw text-sm py-1 auto-submit">
					@foreach($directionOptions as $value => $label)
					<option value="{{ $value }}" {{ ($direction ?? 'asc') == $value ? 'selected' : '' }}>{{ $label }}</option>
					@endforeach
				</select>
			</form>
		</div>
	</div>

	<!-- Reviews List -->
	@if (isset($reviews) && count($reviews) > 0)
		<div>
			<!-- Top Pagination -->
			<div class="mb-4">
				{!! $reviews->appends(['sort' => $sort, 'limit' => $limit, 'direction' => $direction])->links() !!}
			</div>

			<!-- Reviews -->
			@include('reviews.list-tw', ['reviews' => $reviews])

			<!-- Bottom Pagination -->
			<div class="mt-4">
				{!! $reviews->appends(['sort' => $sort, 'limit' => $limit, 'direction' => $direction])->links() !!}
			</div>
		</div>
	@else
		<div class="text-center py-12 bg-card rounded-lg border border-border">
			<i class="bi bi-star text-4xl text-muted-foreground/50 mb-3 block"></i>
			<p class="text-muted-foreground">No reviews found matching your criteria.</p>
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
