@extends('app')

@section('title','Forum')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Forum</h1>
	<p class="text-gray-400">Discussions, blog posts, and community threads.</p>
</div>

<!-- Action Menu -->
<div class="mb-6 flex flex-wrap gap-2">
	<a href="{{ url('/threads/all') }}" class="inline-flex items-center px-3 py-2 bg-dark-surface border border-dark-border text-gray-300 rounded-lg hover:bg-dark-card transition-colors text-sm">
		Show All Threads
	</a>
	<a href="{!! URL::route('threads.index') !!}" class="inline-flex items-center px-3 py-2 bg-dark-surface border border-dark-border text-gray-300 rounded-lg hover:bg-dark-card transition-colors text-sm">
		Show Paged Threads
	</a>
	<a href="{!! URL::route('threads.create') !!}" class="inline-flex items-center px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors">
		<i class="bi bi-plus-lg mr-2"></i>
		Add Thread
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
		<a href="{{ url()->action('ThreadsController@rppReset') }}" class="inline-flex items-center px-3 py-1 text-sm text-gray-300 hover:text-white border border-dark-border rounded-lg">
			Clear All <i class="bi bi-x ml-1"></i>
		</a>
	</div>
	@endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-dark-surface border border-dark-border rounded-lg p-4 mb-6">
	{!! Form::open(['route' => [$filterRoute ?? 'threads.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
	
	<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
		<!-- Name Filter -->
		<div>
			<label for="filter_name" class="block text-sm font-medium text-gray-300 mb-1">Name</label>
			<input type="text" 
				name="filters[name]" 
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="Thread name...">
		</div>

		<!-- User Filter -->
		<div>
			<label for="filter_user" class="block text-sm font-medium text-gray-300 mb-1">User</label>
			{!! Form::select('filter_user', $userOptions, ($filters['user'] ?? null),
			[
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a user',
				'name' => 'filters[user]',
				'id' => 'filter_user'
			])
			!!}
		</div>

		<!-- Tag Filter -->
		<div>
			<label for="filter_tag" class="block text-sm font-medium text-gray-300 mb-1">Tag</label>
			{!! Form::select('filter_tag', $tagOptions, ($filters['tag'] ?? null),
			[
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a tag',
				'name' => 'filters[tag]',
				'id' => 'filter_tag'
			])
			!!}
		</div>

		<!-- Series Filter -->
		<div>
			<label for="filter_series" class="block text-sm font-medium text-gray-300 mb-1">Series</label>
			{!! Form::select('filter_series', $seriesOptions, ($filters['series'] ?? null),
			[
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a series',
				'name' => 'filters[series]',
				'id' => 'filter_series'
			])
			!!}
		</div>
	</div>

	<!-- Filter Actions -->
	<div class="flex gap-2 mt-4">
		<button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
			Apply
		</button>
		{!! Form::close() !!}
		{!! Form::open(['route' => ['threads.reset'], 'method' => 'GET']) !!}
		<button type="submit" class="px-4 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors">
			Reset
		</button>
		{!! Form::close() !!}
	</div>
</div>

<!-- Results Bar -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
	<!-- Results Count -->
	<div class="text-sm text-gray-400">
		@if(isset($threads))
		Showing {{ $threads->firstItem() ?? 0 }} to {{ $threads->lastItem() ?? 0 }} of {{ $threads->total() }} results
		@endif
	</div>

	<!-- Sort Controls -->
	<div class="flex items-center gap-4">
		<form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
			<select name="rpp" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($rppOptions as $value => $label)
				<option value="{{ $value }}" {{ ($rpp ?? 25) == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<span class="text-gray-400 text-sm">Sort by:</span>
			<select name="sortBy" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($sortOptions as $value => $label)
				<option value="{{ $value }}" {{ ($sortBy ?? 'threads.created_at') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<select name="sortDirection" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($directionOptions as $value => $label)
				<option value="{{ $value }}" {{ ($sortDirection ?? 'desc') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
		</form>
	</div>
</div>

<!-- Threads List -->
@if (isset($threads) && count($threads) > 0)
<div class="space-y-4">
	@foreach ($threads as $thread)
	@include('threads.card-tw', ['thread' => $thread])
	@endforeach
</div>

<!-- Pagination -->
<div class="mt-6">
	{!! $threads->onEachSide(2)->links('vendor.pagination.tailwind') !!}
</div>
@else
<div class="text-center py-12">
	<i class="bi bi-chat-dots text-6xl text-gray-600 mb-4"></i>
	<p class="text-gray-400">No threads found.</p>
</div>
@endif

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
