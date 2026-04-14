@extends('layouts.app-tw')

@section('title', 'Blogs')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@if (isset($blogs) && count($blogs) > 0)
@section('page.json')
@include('blogs.index-json-ld')
@endsection
@endif

@section('content')

<div class="container mx-auto">
	<!-- Page Header -->
	<div class="flex justify-between items-center mb-6">
		<h1 class="text-3xl font-bold text-primary">Blogs</h1>
		<a href="{{ route('blogs.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
			<i class="bi bi-plus-circle mr-2"></i>
			Add Blog
		</a>
	</div>

	<!-- Filters -->
	<div class="card-tw mb-6">
		<div class="p-4">
			<div class="flex flex-wrap items-center gap-2">
				<button type="button" id="filters-toggle-btn" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
					<i class="bi bi-funnel mr-2"></i>
					<span id="filters-toggle-text">Show Filters</span>
					<i class="bi bi-chevron-down ml-2 transition-transform" id="filters-icon"></i>
				</button>

				@if($hasFilter)
				<div id="active-filters-badges" class="inline-flex flex-wrap items-center gap-2">
					@if(!empty($filters['name']))
					<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
						Name: {{ $filters['name'] }}
					</span>
					@endif
					@if(!empty($filters['body']))
					<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
						Body: {{ $filters['body'] }}
					</span>
					@endif
					@if(!empty($filters['user']))
					<span class="px-3 py-1 text-sm bg-muted text-muted-foreground rounded-lg border border-border">
						User: {{ $userOptions[$filters['user']] ?? 'Unknown' }}
					</span>
					@endif
				</div>
				<a id="filters-reset-closed" href="{{ route('blogs.reset') }}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg">
					Reset <i class="bi bi-x ml-1"></i>
				</a>
				@endif
			</div>

			<div id="filters-content" class="hidden mt-4">
				<form action="{{ route('blogs.filter') }}" method="POST">
					@csrf
					<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
						<div>
							<label for="filter_name" class="block text-sm font-medium text-foreground mb-1">Name</label>
							<input type="text" name="filters[name]" id="filter_name" value="{{ $filters['name'] ?? '' }}" class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
						</div>

						<div>
							<label for="filter_body" class="block text-sm font-medium text-foreground mb-1">Body</label>
							<input type="text" name="filters[body]" id="filter_body" value="{{ $filters['body'] ?? '' }}" class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
						</div>

						<div>
							<label for="filter_user" class="block text-sm font-medium text-foreground mb-1">User</label>
							<select name="filters[user]" id="filter_user" class="form-control select2 w-full">
								<option value="">Select a user</option>
								@foreach($userOptions as $id => $name)
									<option value="{{ $id }}" {{ ($filters['user'] ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
								@endforeach
							</select>
						</div>
					</div>

					<div class="flex gap-2">
						<button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
							Apply Filters
						</button>
						<a id="filters-reset-open" href="{{ route('blogs.reset') }}" class="px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 transition-colors hidden">
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
				<a href="{{ url()->action('BlogsController@rppReset') }}" class="p-2 bg-muted hover:bg-muted/80 rounded-lg transition-colors" title="Reset list controls">
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
		{!! $blogs->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links('vendor.pagination.tailwind') !!}
	</div>

	<!-- Blogs List -->
	@include('blogs.list-tw', ['blogs' => $blogs])

	<!-- Pagination -->
	<div class="mt-4">
		{!! $blogs->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links('vendor.pagination.tailwind') !!}
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

	const badges = document.getElementById('active-filters-badges');
	const toggleText = document.getElementById('filters-toggle-text');
	const resetClosed = document.getElementById('filters-reset-closed');
	const resetOpen = document.getElementById('filters-reset-open');
	const hasFilter = @json($hasFilter);

	function applyState(open) {
		filtersContent.classList.toggle('hidden', !open);
		if (filtersIcon) filtersIcon.classList.toggle('rotate-180', open);
		if (toggleText) toggleText.textContent = open ? 'Hide Filters' : 'Show Filters';
		if (badges) badges.classList.toggle('hidden', open);
		if (resetClosed) resetClosed.classList.toggle('hidden', open || !hasFilter);
		if (resetOpen) resetOpen.classList.toggle('hidden', !open || !hasFilter);
	}

	const storageKey = 'filter-open:' + window.location.pathname;
	const saved = localStorage.getItem(storageKey);
	if (saved !== null) applyState(saved === '1');

	if (toggleBtn) {
		toggleBtn.addEventListener('click', function() {
			const open = filtersContent.classList.contains('hidden');
			applyState(open);
			localStorage.setItem(storageKey, open ? '1' : '0');
		});
	}

	// Auto-submit on select change
	document.querySelectorAll('.auto-submit').forEach(function(select) {
		select.addEventListener('change', function() {
			this.form.submit();
		});
	});

	// Initialize Select2
	$('#filter_user').select2({
		theme: 'tailwind',
		placeholder: 'Select a user'
	});
});
</script>
@stop
