@extends('layouts.app-tw')

@section('title','Users')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
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

<!-- Filters -->
<x-filter-panel :action="route('users.filter')" :reset-url="route('users.reset')" :has-filter="$hasFilter">
	<x-slot:badges>
		@if(!empty($filters['email']))
		<x-filter-badge>Email: {{ $filters['email'] }}</x-filter-badge>
		@endif
		@if(!empty($filters['name']))
		<x-filter-badge>Name: {{ $filters['name'] }}</x-filter-badge>
		@endif
		@if(!empty($filters['status']))
		<x-filter-badge>Status: {{ $userStatusOptions[$filters['status']] ?? 'Unknown' }}</x-filter-badge>
		@endif
	</x-slot:badges>

	<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
		<!-- Email Filter -->
		<div class="min-w-0">
			<label for="filter_email" class="block text-sm font-medium text-muted-foreground mb-1">Email</label>
			<input type="text" 
				name="filters[email]" 
				id="filter_email"
				value="{{ $filters['email'] ?? '' }}"
				class="form-input-tw"
				placeholder="User email...">
		</div>

		<!-- Name Filter -->
		<div class="min-w-0">
			<label for="filter_name" class="block text-sm font-medium text-muted-foreground mb-1">Name</label>
			<input type="text" 
				name="filters[name]" 
				id="filter_name"
				value="{{ $filters['name'] ?? '' }}"
				class="form-input-tw"
				placeholder="User name...">
		</div>

		<!-- Status Filter -->
		<div class="min-w-0">
			<label for="filter_status" class="block text-sm font-medium text-muted-foreground mb-1">Status</label>
			<x-ui.select-field name="filters[status]" :options="$userStatusOptions" :selected="$filters['status'] ?? null"
				data-theme="tailwind" class="form-select-tw select2" data-placeholder="Select a status" id="filter_status" />
		</div>
	</div>
</x-filter-panel>

<!-- Results Bar -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
	<!-- Results Count -->
	<div class="text-sm text-muted-foreground">
		@if(isset($users))
		Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} results
		@endif
	</div>

	<!-- Sort Controls -->
	<div class="flex items-center gap-4">
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
	</div>

	<!-- Pagination (top) -->
	@if(isset($users) && $users->hasPages())
	<div class="flex items-center gap-1">
		@if($users->onFirstPage())
		<span class="px-3 py-1 text-muted-foreground/50 cursor-not-allowed">&lt; Previous</span>
		@else
		<a href="{{ $users->previousPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">&lt; Previous</a>
		@endif

		@foreach($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
		<a href="{{ $url }}" class="px-3 py-1 rounded {{ $page == $users->currentPage() ? 'bg-accent text-foreground border border-primary' : 'text-muted-foreground hover:bg-card' }}">{{ $page }}</a>
		@endforeach

		@if($users->hasMorePages())
		<a href="{{ $users->nextPageUrl() }}" class="px-3 py-1 text-muted-foreground hover:text-foreground">Next &gt;</a>
		@else
		<span class="px-3 py-1 text-muted-foreground/50 cursor-not-allowed">Next &gt;</span>
		@endif
	</div>
	@endif
</div>

	<!-- Users Grid -->
	@if (isset($users) && count($users) > 0)
	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
		@foreach ($users as $user)
		@include('users.card-tw', ['user' => $user])
		@endforeach
	</div>
	@else
	<x-empty-state icon="bi-people" boxed>No users found.</x-empty-state>
	@endif
</div>

@stop

