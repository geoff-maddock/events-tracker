@extends('layouts.app-tw')

@section('title', 'Categories')

@section('content')

<div class="container mx-auto">
	<!-- Page Header -->
	<div class="flex justify-between items-center mb-6">
		<h1 class="text-3xl font-bold text-primary">Categories</h1>
		@can('edit_category')
		<a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
			<i class="bi bi-plus-circle mr-2"></i>
			Add Category
		</a>
		@endcan
	</div>

	<!-- Filters -->
	<x-filter-panel :action="route('categories.filter')" :reset-url="route('categories.reset')" :has-filter="$hasFilter" :open="$hasFilter">
		<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
			<div>
				<label for="filter_name" class="block text-sm font-medium text-foreground mb-1">Name</label>
				<input type="text" name="filters[name]" id="filter_name" value="{{ $filters['name'] ?? '' }}" class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
			</div>
		</div>
	</x-filter-panel>

	<!-- List Controls -->
	<div class="card-tw mb-6">
		<div class="p-4">
			<form action="{{ url()->current() }}" method="GET" class="flex flex-wrap items-center gap-3">
				<a href="{{ url()->action([\App\Http\Controllers\CategoriesController::class, 'rppReset']) }}" class="p-2 bg-muted hover:bg-muted/80 rounded-lg transition-colors" title="Reset list controls">
					<i class="bi bi-arrow-clockwise"></i>
				</a>

				<select name="limit" class="px-3 py-2 bg-input border border-input rounded-lg text-foreground auto-submit">
					@foreach($limitOptions as $value => $label)
						<option value="{{ $value }}" {{ $limit == $value ? 'selected' : '' }}>{{ $label }} per page</option>
					@endforeach
				</select>

				<select name="sort" class="px-3 py-2 bg-input border border-input rounded-lg text-foreground auto-submit">
					@foreach($sortOptions as $value => $label)
						<option value="{{ $value }}" {{ $sort == $value ? 'selected' : '' }}>Sort: {{ $label }}</option>
					@endforeach
				</select>

				<select name="direction" class="px-3 py-2 bg-input border border-input rounded-lg text-foreground auto-submit">
					@foreach($directionOptions as $value => $label)
						<option value="{{ $value }}" {{ $direction == $value ? 'selected' : '' }}>{{ ucfirst($label) }}</option>
					@endforeach
				</select>
			</form>
		</div>
	</div>

	<!-- Pagination -->
	<div class="mb-4">
		{!! $categories->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links('vendor.pagination.tailwind') !!}
	</div>

	<!-- Categories List -->
	@include('categories.list-tw', ['categories' => $categories])

	<!-- Pagination -->
	<div class="mt-4">
		{!! $categories->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links('vendor.pagination.tailwind') !!}
	</div>
</div>

@stop

@section('scripts.footer')
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Auto-submit on select change
	document.querySelectorAll('.auto-submit').forEach(function(select) {
		select.addEventListener('change', function() {
			this.form.submit();
		});
	});
});
</script>
@stop
