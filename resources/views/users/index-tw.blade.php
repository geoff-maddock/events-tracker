@extends('layouts.app-tw')

@section('title','Users')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<div class="flex flex-col gap-6">
	<!-- Page Header -->
	<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
		<div>
			<h1 class="text-2xl font-bold text-foreground">Users</h1>
			<p class="text-muted-foreground">Public user directory</p>
		</div>
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
		<a href="{{ url()->action('UsersController@rppReset') }}" class="inline-flex items-center px-3 py-1 text-sm text-muted-foreground hover:text-foreground border border-border rounded-lg">
			Clear All <i class="bi bi-x ml-1"></i>
		</a>
	</div>
	@endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-card border border-border rounded-lg p-4 mb-6">
	{!! Form::open(['route' => ['users.filter'], 'name' => 'filters', 'method' => 'POST']) !!}

	<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
		<!-- Email Filter -->
		<div>
			<label for="filter_email" class="block text-sm font-medium text-muted-foreground mb-1">Email</label>
			<input type="text" 
				name="filters[email]" 
				id="filter_email"
				value="{{ $filters['email'] ?? '' }}"
				class="form-input-tw"
				placeholder="User email...">
		</div>

		<!-- Name Filter -->
		<div>
			<label for="filter_name" class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
			<input type="text" 
				name="filters[name]" 
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="User name...">
		</div>

		<!-- Status Filter -->
		<div>
			<label for="filter_status" class="block text-sm font-medium text-muted-foreground mb-1">Status</label>
			{!! Form::select('filter_status', $userStatusOptions, ($filters['status'] ?? null),
			[
				'class' => 'form-select-tw select2',
				'data-placeholder' => 'Select a status',
				'name' => 'filters[status]',
				'id' => 'filter_status'
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
		{!! Form::open(['route' => ['users.reset'], 'method' => 'GET']) !!}
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
		@if(isset($users))
		Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} results
		@endif
	</div>

	<!-- Sort Controls & Pagination -->
	<div class="flex flex-wrap items-center gap-4">
		<form action="{{ url()->current() }}" method="GET" class="flex items-center gap-2">
			<select name="limit" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($limitOptions as $value => $label)
				<option value="{{ $value }}" {{ ($limit ?? 25) == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<span class="text-muted-foreground text-sm">Sort by:</span>
			<select name="sort" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($sortOptions as $value => $label)
				<option value="{{ $value }}" {{ ($sort ?? 'users.name') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<select name="direction" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($directionOptions as $value => $label)
				<option value="{{ $value }}" {{ ($direction ?? 'asc') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
		</form>

		<!-- Pagination -->
		@if(isset($users) && $users->hasPages())
		<div class="flex items-center gap-1">
			<span class="text-muted-foreground mr-1 hidden lg:inline">|</span>
			@if($users->onFirstPage())
			<span class="px-3 py-1 text-muted-foreground/50 cursor-not-allowed">&lt; Previous</span>
			@else
			<a href="{{ $users->previousPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">&lt; Previous</a>
			@endif

			@foreach($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
			<a href="{{ $url }}" class="px-3 py-1 rounded {{ $page == $users->currentPage() ? 'bg-accent text-foreground border-2 border-primary' : 'text-muted-foreground hover:bg-card' }}">{{ $page }}</a>
			@endforeach

			@if($users->hasMorePages())
			<a href="{{ $users->nextPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">Next &gt;</a>
			@else
			<span class="px-3 py-1 text-muted-foreground/50 cursor-not-allowed">Next &gt;</span>
			@endif
		</div>
		@endif
	</div>
</div>

	<!-- Users Grid -->
	@if (isset($users) && count($users) > 0)
	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
		@foreach ($users as $user)
		@include('users.card-tw', ['user' => $user])
		@endforeach
	</div>
	@else
	<div class="text-center py-12 bg-card rounded-lg border border-border">
		<i class="bi bi-people text-4xl text-muted-foreground/50 mb-3 block"></i>
		<p class="text-muted-foreground">No users found.</p>
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
