@extends('layouts.app-tw')

@section('title', 'Categories')

@section('content')

<div class="container mx-auto">
	<!-- Page Header -->
	<div class="flex justify-between items-center mb-6">
		<h1 class="text-3xl font-bold text-primary">Categories</h1>
		<a href="{{ route('categories.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
			<i class="bi bi-plus-circle mr-2"></i>
			Add Category
		</a>
	</div>

	<!-- Filters -->
	<div class="card-tw mb-6">
		<div class="p-4">
			<button type="button" id="filters-toggle-btn" class="flex items-center gap-2 text-foreground hover:text-primary transition-colors">
				<i class="bi bi-funnel text-lg"></i>
				<span class="font-medium">Filters</span>
				<i class="bi bi-chevron-down transition-transform" id="filters-icon"></i>
			</button>

			<div id="filters-content" class="{{ $hasFilter ? '' : 'hidden' }} mt-4">
				<form action="{{ route('categories.filter') }}" method="POST">
					@csrf
					<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
						<div>
							<label for="filter_name" class="block text-sm font-medium text-foreground mb-1">Name</label>
							<input type="text" name="filters[name]" id="filter_name" value="{{ $filters['name'] ?? '' }}" class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
						</div>
					</div>

					<div class="flex gap-2">
						<button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
							Apply Filters
						</button>
						<a href="{{ route('categories.reset') }}" class="px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 transition-colors">
							Reset
						</a>
					</div>
				</form>
			</div>
		</div>
	</div>

	<!-- List Controls -->
	<div class="card-tw mb-6">
		<div class="p-4">
			<form action="{{ url()->current() }}" method="GET" class="flex flex-wrap items-center gap-3">
				<a href="{{ url()->action('CategoriesController@rppReset') }}" class="p-2 bg-muted hover:bg-muted/80 rounded-lg transition-colors" title="Reset list controls">
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
		{!! $categories->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links() !!}
	</div>

	<!-- Categories List -->
	@include('categories.list-tw', ['categories' => $categories])

	<!-- Pagination -->
	<div class="mt-4">
		{!! $categories->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links() !!}
	</div>
</div>

@stop

@section('scripts.footer')
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Filters toggle
	const toggleBtn = document.getElementById('filters-toggle-btn');
	const filtersContent = document.getElementById('filters-content');
	const filtersIcon = document.getElementById('filters-icon');

	if (toggleBtn) {
		toggleBtn.addEventListener('click', function() {
			filtersContent.classList.toggle('hidden');
			filtersIcon.classList.toggle('rotate-180');
		});
	}

	// Auto-submit on select change
	document.querySelectorAll('.auto-submit').forEach(function(select) {
		select.addEventListener('change', function() {
			this.form.submit();
		});
	});
});
</script>
@stop
