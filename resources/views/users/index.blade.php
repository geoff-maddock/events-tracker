@extends('app')

@section('title','Users')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.1.1/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('content')

<!-- Page Header -->
<div class="mb-6">
	<h1 class="text-3xl font-bold text-primary mb-2">Users</h1>
	<p class="text-gray-400">Public user directory</p>
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
		<a href="{{ url()->action('UsersController@rppReset') }}" class="inline-flex items-center px-3 py-1 text-sm text-gray-300 hover:text-white border border-dark-border rounded-lg">
			Clear All <i class="bi bi-x ml-1"></i>
		</a>
	</div>
	@endif
</div>

<!-- Filter Panel -->
<div id="filter-panel" class="@if(!$hasFilter) hidden @endif bg-dark-surface border border-dark-border rounded-lg p-4 mb-6">
	{!! Form::open(['route' => ['users.filter'], 'name' => 'filters', 'method' => 'POST']) !!}
	
	<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
		<!-- Email Filter -->
		<div>
			<label for="filter_email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
			<input type="text" 
				name="filters[email]" 
				id="filter_email"
				value="{{ $filters['email'] ?? '' }}"
				class="form-input-tw"
				placeholder="User email...">
		</div>

		<!-- Name Filter -->
		<div>
			<label for="filter_name" class="block text-sm font-medium text-gray-300 mb-1">Name</label>
			<input type="text" 
				name="filters[name]" 
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="User name...">
		</div>

		<!-- Status Filter -->
		<div>
			<label for="filter_status" class="block text-sm font-medium text-gray-300 mb-1">Status</label>
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
		<button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
			Apply
		</button>
		{!! Form::close() !!}
		{!! Form::open(['route' => ['users.reset'], 'method' => 'GET']) !!}
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
		@if(isset($users))
		Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} results
		@endif
	</div>

	<!-- Sort Controls -->
	<div class="flex items-center gap-4">
		<form action="{{ url()->action('UsersController@filter') }}" method="GET" class="flex items-center gap-2">
			<select name="rpp" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($rppOptions as $value => $label)
				<option value="{{ $value }}" {{ ($rpp ?? 25) == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<span class="text-gray-400 text-sm">Sort by:</span>
			<select name="sortBy" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($sortOptions as $value => $label)
				<option value="{{ $value }}" {{ ($sortBy ?? 'name') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
			<select name="sortDirection" class="form-select-tw text-sm py-1 auto-submit">
				@foreach($directionOptions as $value => $label)
				<option value="{{ $value }}" {{ ($sortDirection ?? 'asc') == $value ? 'selected' : '' }}>{{ $label }}</option>
				@endforeach
			</select>
		</form>
	</div>
</div>

<!-- Users Grid -->
@if (isset($users) && count($users) > 0)
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
	@foreach ($users as $user)
	@include('users.card-tw', ['user' => $user])
	@endforeach
</div>

<!-- Pagination -->
<div class="mt-6">
	{!! $users->onEachSide(2)->links('vendor.pagination.tailwind') !!}
</div>
@else
<div class="text-center py-12">
	<i class="bi bi-people text-6xl text-gray-600 mb-4"></i>
	<p class="text-gray-400">No users found.</p>
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
