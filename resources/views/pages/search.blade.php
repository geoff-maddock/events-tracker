@extends('app')

@section('title','Search Results')

@section('content')

<h1 class="display-6 text-primary">Search
		@include('events.crumbs')
</h1>

<div id="action-menu" class="mb-2">
	<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show event index</a>
	<a href="{!! URL::route('calendar') !!}" class="btn btn-info">Show event calendar</a>
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a>
	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	<a href="{!! URL::route('entities.create') !!}" class="btn btn-primary">Add an entity</a>
	<a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add a thread</a>
</div>

<div class="row">
	@if (isset($entities) && $entitiesCount > 0)
	<div class="col-xl-6">
	<div class="card surface my-2">

		<h5 class="card-header bg-primary">Entities
			<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom"  title="# of Entities that match this search term.">{{ $entitiesCount}}</span></a>
			<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-popular-close-box" data-bs-target="#search-entities" data-bs-toggle="collapse" aria-expanded="false" aria-controls="search-entities" role="button"></i></a>
		</h5>

		<div class="card-body collapsible collapse show" id="search-entities">
				@include('entities.list', ['entities' => $entities])
				{!! $entities->appends(['keyword' => $search])->render() !!}
		</div>
	</div>
	</div>
	@else

	<div class="bs-component">
		No matching entities found.
	</div>
	@endif
</div>

<div class="row">
	@if (isset($events) && $eventsCount > 0)
	<div class="col-xl-6">
		@if (isset($tags) && $tagsCount > 0)
			<div class="card surface">
				<h5 class="card-header bg-primary">Tags
					<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom" title="# of Tags that match this search term.">{{ $tagsCount }}</span></a>
					<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-popular-close-box" data-bs-target="#search-tags" data-bs-toggle="collapse" aria-expanded="false" aria-controls="search-tags" role="button"></i></a>
				</h5>

				<div class="card-body collapsible collapse show" id="search-tags">
				@include('tags.list', ['tags' => $tags])
				{!! $tags->appends(['keyword' => $search])->render() !!}
				</div>

			</div>
		@endif

			<div class="card surface my-2">

				<h5 class="card-header bg-primary">Events
					<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom"  title="# of Events that match this search term.">{{ $eventsCount}}</span></a>
					<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-popular-close-box" data-bs-target="#search-events" data-bs-toggle="collapse" aria-expanded="false" aria-controls="search-events" role="button"></i></a>
				</h5>

				<div class="card-body collapsible collapse show" id="search-events">
				@include('events.list', ['events' => $events])
				{!! $events->appends(['keyword' => $search])->links() !!}
				</div>

			</div>
	</div>
	@else
		<div class="col-lg-12">
			<div class="bs-component">
					No matching events found.
			</div>
		</div>
	@endif
	</div>

<div class="row">
	@if (isset($series) && $seriesCount > 0)
	<div class="col-xl-6">
		<div class="card surface">
			<h5 class="card-header bg-primary">Series
					<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom"  title="# of Series that match this search term.">{{ $seriesCount}}</span></a>
					<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-popular-close-box" data-bs-target="#search-series" data-bs-toggle="collapse" aria-expanded="false" aria-controls="search-series" role="button"></i></a>
			</h5>
			
			<div class="card-body collapsible collapse show" id="search-series">
			@include('series.list', ['series' => $series])
			{!! $series->appends(['keyword' => $search])->render() !!}
			</div>

		</div>
	</div>

	@else
	<div class="bs-component">
		No matching series found.
	</div>
	@endif
</div>

	<div class="col-xl-6">
		@if (isset($users) && $usersCount > 0)
		<div class="card surface my-2">

			<h5 class="card-header bg-primary">Users
				<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom"  title="# of Users that match this search term.">{{ $usersCount}}</span></a>
				<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-popular-close-box" data-bs-target="#search-users" data-bs-toggle="collapse" aria-expanded="false" aria-controls="search-users" role="button"></i></a>
			</h5>

			<div class="card-body collapsible collapse show" id="search-users">
			@include('users.list', ['users' => $users])
			{!! $users->appends(['keyword' => $search])->links() !!}
			</div>

		</div>
		@else

				<div class="bs-component">

					No matching users found.

				</div>

		@endif
	</div>

	<div class="col-xl-6">
		@if (isset($threads) && $threadsCount > 0)
		<div class="card surface my-2">

			<h5 class="card-header bg-primary">Threads
				<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom"  title="# of Threads that match this search term.">{{ $threadsCount}}</span></a>
				<a href="#" class="float-end px-1"  title="Show / Hide"><i class="bi bi-eye-fill toggler" id="tag-popular-close-box" data-bs-target="#search-threads" data-bs-toggle="collapse" aria-expanded="false" aria-controls="search-threads" role="button"></i></a>
			</h5>

			<div class="card-body collapsible collapse show" id="search-threads">
				@include('threads.list', ['threads' => $threads])
				{!! $threads->appends(['keyword' => $search])->render() !!}
			</div>
		</div>
		@else

		<div class="bs-component">
			No matching threads found.
		</div>

		@endif
	</div>
</div>

@stop
 