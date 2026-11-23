@extends('layouts.app-tw')

@section('title','Reviews')

@section('content')

<div class="flex flex-col gap-6">
	<!-- Header -->
	<div>
		<h1 class="text-2xl font-bold text-foreground">Reviews @include('reviews.crumbs')</h1>
	</div>

	<!-- Filters -->
	<x-filter-panel :action="route($filterRoute ?? 'reviews.filter')" :reset-url="route('reviews.reset')" :has-filter="$hasFilter" :open="$hasFilter">
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
				<x-ui.select-field name="filters[user]" :options="$userOptions" :selected="$filters['user'] ?? null"
					data-theme="bootstrap-5" class="form-select-tw select2" data-placeholder="Select a user" id="filter_user" />
			</div>

			<!-- Review Type Filter -->
			<div>
				<label for="filter_review_type" class="block text-sm font-medium text-muted-foreground mb-1">Type</label>
				<x-ui.select-field name="filters[review_type]" :options="$reviewTypeOptions" :selected="$filters['review_type'] ?? null"
					data-theme="bootstrap-5" class="form-select-tw select2" data-placeholder="Select a type" id="filter_review_type" />
			</div>
		</div>
	</x-filter-panel>

	<!-- Sort Controls -->
	<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
		<div class="text-sm text-muted-foreground">
			@if(isset($reviews))
			Showing {{ $reviews->firstItem() ?? 0 }} to {{ $reviews->lastItem() ?? 0 }} of {{ $reviews->total() }} results
			@endif
		</div>

		<div class="flex flex-wrap items-center gap-4">
			<form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
				<a href="{{ url()->action([\App\Http\Controllers\ReviewsController::class, 'rppReset']) }}" class="px-3 py-2 bg-card border border-border rounded-lg hover:bg-accent transition-colors" title="Reset sort and filters">
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
				{!! $reviews->appends(['sort' => $sort, 'limit' => $limit, 'direction' => $direction])->links('vendor.pagination.tailwind') !!}
			</div>

			<!-- Reviews -->
			@include('reviews.list-tw', ['reviews' => $reviews])

			<!-- Bottom Pagination -->
			<div class="mt-4">
				{!! $reviews->appends(['sort' => $sort, 'limit' => $limit, 'direction' => $direction])->links('vendor.pagination.tailwind') !!}
			</div>
		</div>
	@else
		<x-empty-state icon="bi-star" boxed>No reviews found matching your criteria.</x-empty-state>
	@endif
</div>

@stop

