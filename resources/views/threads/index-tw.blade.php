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
	<p class="text-muted-foreground">Discussions and community threads.</p>
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

<!-- Filters -->
<x-filter-panel :action="route($filterRoute ?? 'threads.filter')" :reset-url="route('threads.reset')" :has-filter="$hasFilter" :open="$hasFilter">
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
			<x-ui.select-field name="filters[user]" :options="$userOptions" :selected="$filters['user'] ?? null"
				class="form-select-tw select2" data-placeholder="Select a user" data-theme="tailwind" data-allow-clear="true" id="filter_user" />
		</div>

		<!-- Tag Filter -->
		<div>
			<label for="filter_tag" class="block text-sm font-medium text-muted-foreground mb-1">Tag</label>
			<x-ui.select-field name="filters[tag]" :options="$tagOptions" :selected="$filters['tag'] ?? null"
				class="form-select-tw select2" data-placeholder="Select a tag" data-theme="tailwind" data-allow-clear="true" id="filter_tag" />
		</div>

		<!-- Series Filter -->
		<div>
			<label for="filter_series" class="block text-sm font-medium text-muted-foreground mb-1">Series</label>
			<x-ui.select-field name="filters[series]" :options="$seriesOptions" :selected="$filters['series'] ?? null"
				class="form-select-tw select2" data-placeholder="Select a series" data-theme="tailwind" data-allow-clear="true" id="filter_series" />
		</div>
	</div>
</x-filter-panel>

<x-list-toolbar heading="Threads" :paginator="$threads ?? null" :limit="$limit ?? 10" :sort="$sort ?? 'threads.created_at'" :direction="$direction ?? 'desc'"
	:limit-options="$limitOptions" :sort-options="$sortOptions" :direction-options="$directionOptions" />

<!-- Threads List -->
@if (isset($threads) && count($threads) > 0)
<div class="border border-border rounded-lg overflow-hidden">
	<!-- Column header (lg+) -->
	<div class="hidden lg:flex items-center gap-3 px-4 py-2 bg-muted border-b border-border text-xs font-semibold text-muted-foreground uppercase tracking-wide">
		<div class="w-8 flex-shrink-0"></div>
		<div class="flex-1">Thread</div>
		<div class="w-28 text-center flex-shrink-0">Author</div>
		<div class="w-20 text-center flex-shrink-0">Replies</div>
		<div class="w-32 text-right flex-shrink-0">Last Activity</div>
		<div class="w-4 flex-shrink-0"></div>
	</div>
	<!-- Rows -->
	<div class="divide-y divide-border">
		@foreach ($threads as $thread)
		@include('threads.card-tw', ['thread' => $thread])
		@endforeach
	</div>
</div>
@else
<x-empty-state icon="bi-chat-dots">No threads found.</x-empty-state>
@endif

@stop

