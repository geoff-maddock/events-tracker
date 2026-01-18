@extends('layouts.app-tw')

@section('title','Forum')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Forum</h1>
	<p class="text-muted-foreground">Discussions, blog posts, and community threads.</p>
</div>

<!-- Action Menu -->
<div class="mb-6 flex flex-wrap gap-2">
	<a href="{{ url('/threads/all') }}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Show All Threads
	</a>
	<a href="{!! URL::route('threads.index') !!}" class="inline-flex items-center px-3 py-2 bg-card border border-border text-muted-foreground rounded-lg hover:bg-card transition-colors text-sm">
		Show Paged Threads
	</a>
	<a href="{!! URL::route('threads.create') !!}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
		<i class="bi bi-plus-lg mr-2"></i>
		Add Thread
	</a>
</div>

<!-- Filters Section -->
<div class="mb-6">
	<button id="filters-toggle-btn" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
		<i class="bi bi-funnel mr-2"></i>
		<span id="filters-toggle-text">@if($hasFilter) Hide @else Show @endif Filters</span>
		<i class="bi bi-chevron-down ml-2 transition-transform @if($hasFilter) rotate-180 @endif" id="filters-chevron"></i>
	</button>

	<!-- Active Filters / Reset -->
	@if($hasFilter)
	<div class="inline-flex items-center gap-2 ml-4">
		<a href="{{ url()->action('ThreadsController@rppReset') }}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg">
			Clear All <i class="bi bi-x ml-1"></i>
		</a>
	</div>
	@endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6">
	{!! Form::open(['route' => [$filterRoute ?? 'threads.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

	<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
		<!-- Name Filter -->
		<div>
			<label for="filter_name" class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
			<input type="text" 
				name="filters[name]" 
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="Thread name...">
		</div>

		<!-- User Filter -->
		<div>
			<label for="filter_user" class="block text-sm font-medium text-muted-foreground mb-1">User</label>
			{!! Form::select('filter_user', $userOptions, ($filters['user'] ?? null),
			[
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a user',
				'data-theme' => 'tailwind',
				'data-allow-clear' => 'true',
				'name' => 'filters[user]',
				'id' => 'filter_user'
			])
			!!}
		</div>

		<!-- Tag Filter -->
		<div>
			<label for="filter_tag" class="block text-sm font-medium text-muted-foreground mb-1">Tag</label>
			{!! Form::select('filter_tag', $tagOptions, ($filters['tag'] ?? null),
			[
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a tag',
				'data-theme' => 'tailwind',
				'data-allow-clear' => 'true',
				'name' => 'filters[tag]',
				'id' => 'filter_tag'
			])
			!!}
		</div>

		<!-- Series Filter -->
		<div>
			<label for="filter_series" class="block text-sm font-medium text-muted-foreground mb-1">Series</label>
			{!! Form::select('filter_series', $seriesOptions, ($filters['series'] ?? null),
			[
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a series',
				'data-theme' => 'tailwind',
				'data-allow-clear' => 'true',
				'name' => 'filters[series]',
				'id' => 'filter_series'
			])
			!!}
		</div>
	</div>

	<!-- Filter Actions -->
	<div class="flex gap-2 mt-4">
		<button type="submit" class="px-4 py-2 bg-accent text-foreground border border-primary rounded-lg hover:bg-accent/80 transition-colors">
			Apply
		</button>
		{!! Form::close() !!}
		{!! Form::open(['route' => ['threads.reset'], 'method' => 'GET']) !!}
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
		@if(isset($threads))
		Showing {{ $threads->firstItem() ?? 0 }} to {{ $threads->lastItem() ?? 0 }} of {{ $threads->total() }} results
		@endif
	</div>

	<!-- Sort Controls & Pagination -->
	<div class="flex flex-wrap items-center gap-4">
		<form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
			<select name="limit" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($limitOptions as $value => $label)
				<option value="{{ $value }}" {{ ($limit ?? 10) == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<span class="text-muted-foreground text-sm">Sort by:</span>
			<select name="sort" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($sortOptions as $value => $label)
				<option value="{{ $value }}" {{ ($sort ?? 'threads.created_at') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<select name="direction" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($directionOptions as $value => $label)
				<option value="{{ $value }}" {{ ($direction ?? 'desc') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
		</form>

		<!-- Pagination -->
		@if(isset($threads) && $threads->hasPages())
		<div class="flex items-center gap-1">
			<span class="text-muted-foreground mr-1 hidden lg:inline">|</span>
			@if($threads->onFirstPage())
			<span class="px-3 py-1 text-muted-foreground/50 cursor-not-allowed">&lt; Previous</span>
			@else
			<a href="{{ $threads->previousPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">&lt; Previous</a>
			@endif

			@foreach($threads->getUrlRange(max(1, $threads->currentPage() - 2), min($threads->lastPage(), $threads->currentPage() + 2)) as $page => $url)
			<a href="{{ $url }}" class="px-3 py-1 rounded {{ $page == $threads->currentPage() ? 'bg-accent text-foreground border border-primary' : 'text-muted-foreground hover:bg-card' }}">{{ $page }}</a>
			@endforeach

			@if($threads->hasMorePages())
			<a href="{{ $threads->nextPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">Next &gt;</a>
			@else
			<span class="px-3 py-1 text-muted-foreground/50 cursor-not-allowed">Next &gt;</span>
			@endif
		</div>
		@endif
	</div>
</div>

<!-- Threads List -->
@if (isset($threads) && count($threads) > 0)
<div class="space-y-4">
	@foreach ($threads as $thread)
	@include('threads.card-tw', ['thread' => $thread])
	@endforeach
</div>
@else
<div class="text-center py-12">
	<i class="bi bi-chat-dots text-6xl text-muted-foreground/60 mb-4"></i>
	<p class="text-muted-foreground">No threads found.</p>
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
