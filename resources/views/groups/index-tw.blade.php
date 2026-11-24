@extends('layouts.app-tw')

@section('title', 'Groups')

@section('content')

<div class="container mx-auto">
	<!-- Page Header -->
	<div class="flex justify-between items-center mb-6">
		<h1 class="text-3xl font-bold text-primary">Groups</h1>
		<a href="{{ route('groups.create') }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
			<i class="bi bi-plus-circle mr-2"></i>
			Add Group
		</a>
	</div>

	<!-- Filters -->
	<x-filter-panel :action="route('groups.filter')" :reset-url="route('groups.reset')" :has-filter="$hasFilter">
		<x-slot:badges>
			@if(!empty($filters['name']))
			<x-filter-badge>Name: {{ $filters['name'] }}</x-filter-badge>
			@endif
			@if(!empty($filters['label']))
			<x-filter-badge>Label: {{ $filters['label'] }}</x-filter-badge>
			@endif
			@if(!empty($filters['level']))
			<x-filter-badge>Level: {{ $filters['level'] }}</x-filter-badge>
			@endif
		</x-slot:badges>

		<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
			<div>
				<label for="filter_name" class="block text-sm font-medium text-foreground mb-1">Name</label>
				<input type="text" name="filters[name]" id="filter_name" value="{{ $filters['name'] ?? '' }}" class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
			</div>

			<div>
				<label for="filter_label" class="block text-sm font-medium text-foreground mb-1">Label</label>
				<input type="text" name="filters[label]" id="filter_label" value="{{ $filters['label'] ?? '' }}" class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
			</div>

			<div>
				<label for="filter_level" class="block text-sm font-medium text-foreground mb-1">Level</label>
				<input type="text" name="filters[level]" id="filter_level" value="{{ $filters['level'] ?? '' }}" class="w-full px-3 py-2 bg-input border border-input rounded-lg text-foreground">
			</div>
		</div>
	</x-filter-panel>

	<!-- List Controls -->
	<div class="card-tw mb-6">
		<div class="p-4">
			<form action="{{ url()->current() }}" method="GET" class="flex flex-wrap items-center gap-3">
				<a href="{{ url()->action([\App\Http\Controllers\GroupsController::class, 'rppReset']) }}" class="p-2 bg-muted hover:bg-muted/80 rounded-lg transition-colors" title="Reset list controls">
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
		{!! $groups->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links('vendor.pagination.tailwind') !!}
	</div>

	<!-- Groups List -->
	@include('groups.list-tw', ['groups' => $groups])

	<!-- Pagination -->
	<div class="mt-4">
		{!! $groups->appends(['sort' => $sort, 'direction' => $direction, 'limit' => $limit])->onEachSide(2)->links('vendor.pagination.tailwind') !!}
	</div>
</div>

@stop
