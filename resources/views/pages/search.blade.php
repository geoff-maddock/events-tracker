@extends('app')

@section('title','Events')

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

	@if (isset($events) && count($events) > 0)
	<div class="col-lg-6">

		@if (isset($tags) && count($tags) > 0)
			<div class="card bg-dark">
				<h5 class="card-header bg-primary">Tags
					<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom"  title="# of Tags that match this search term.">{{ count($tags)}}</span></a>
				</h5>

				<div class="card-body">
				@include('tags.list', ['tags' => $tags])
				{!! $tags->appends(['keyword' => $slug])->render() !!}
				</div>

			</div>
		@endif

		@if (isset($series) && count($series) > 0)
			<div class="card bg-dark">

				<h5 class="card-header bg-primary">Series
						<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom"  title="# of Series that match this search term.">{{ count($series)}}</span></a>
				</h5>
				
				<div class="card-body">
				@include('series.list', ['series' => $series])
				{!! $series->appends(['keyword' => $slug])->render() !!}
				</div>

			</div>
		@endif

			<div class="card bg-dark my-2">

				<h5 class="card-header bg-primary">Events
						<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom"  title="# of Events that match this search term.">{{ count($events)}}</span></a>
				</h5>

				<div class="card-body">
				@include('events.list', ['events' => $events])
				{!! $events->appends(['keyword' => $slug])->links() !!}
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

	<div class="col-lg-6">
		@if (isset($users) && count($users) > 0)
		<div class="card bg-dark my-2">

			<h5 class="card-header bg-primary">Users
						<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom"  title="# of Users that match this search term.">{{ count($users)}}</span></a>
			</h5>

			<div class="card-body">
			@include('users.list', ['users' => $users])
			{!! $users->appends(['keyword' => $slug])->links() !!}
			</div>

		</div>
		@else

				<div class="bs-component">

					No matching users found.

				</div>

		@endif
	
		@if (isset($entities) && count($entities) > 0)
		<div class="card bg-dark my-2">

			<h5 class="card-header bg-primary">Entities
				<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom"  title="# of Entities that match this search term.">{{ count($entities)}}</span></a>
			</h5>

			<div class="card-body">
					@include('entities.list', ['entities' => $entities])
					{!! $entities->appends(['keyword' => $slug])->render() !!}
			</div>
		</div>
		@else

		<div class="bs-component">
			No matching entities found.
		</div>

		@endif

		@if (isset($threads) && count($threads) > 0)
		<div class="card bg-dark my-2">

			<h5 class="card-header bg-primary">Threads
				<a href="#" ><span class='badge rounded-pill bg-dark' data-toggle="tooltip" data-placement="bottom"  title="# of Threads that match this search term.">{{ count($threads)}}</span></a>
			</h5>

			<div class="card-body">
				@include('threads.list', ['threads' => $threads])
				{!! $threads->appends(['keyword' => $slug])->render() !!}
			</div>
		</div>
		@endif
	</div>
</div>

@stop
 