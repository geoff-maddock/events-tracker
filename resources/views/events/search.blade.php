@extends('app')

@section('title','Events')

@section('content')

	<h1>Search
		@include('events.crumbs')
	</h1>

	<p>
		<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show event index</a>
		<a href="{!! URL::route('calendar') !!}" class="btn btn-info">Show event calendar</a>
		<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a>
		<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
		<a href="{!! URL::route('entities.create') !!}" class="btn btn-primary">Add an entity</a>
		<a href="{!! URL::route('threads.create') !!}" class="btn btn-primary">Add a thread</a>
	</p>

	<br style="clear: left;"/>

	<div class="row">

	@if (isset($events) && count($events) > 0)
	<div class="col-lg-6">

		@if (isset($tags) && count($tags) > 0)
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Tags
						<a href="#" ><span class='label label-tag pull-right' data-toggle="tooltip" data-placement="bottom"  title="# of Tags that match this search term.">{{ count($tags)}}</span></a>
					</h3>
				</div>

				<div class="panel-body">
				@include('tags.list', ['tags' => $tags])
				{!! $tags->appends(['keyword' => $slug])->render() !!}
				</div>

			</div>
		</div>
		@endif

		@if (isset($series) && count($series) > 0)
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Series
						<a href="#" ><span class='label label-tag pull-right' data-toggle="tooltip" data-placement="bottom"  title="# of Series that match this search term.">{{ count($series)}}</span></a>
					</h3>
				</div>

				<div class="panel-body">
				@include('series.list', ['series' => $series])
				{!! $series->appends(['keyword' => $slug])->render() !!}
				</div>

			</div>
		</div>
		@endif

		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Events
						<a href="#" ><span class='label label-tag pull-right' data-toggle="tooltip" data-placement="bottom"  title="# of Events that match this search term.">{{ count($events)}}</span></a>
					</h3>
				</div>

				<div class="panel-body">
				@include('events.list', ['events' => $events])
				{!! $events->appends(['keyword' => $slug])->links() !!}
				</div>

			</div>
		</div>


	</div>
	@endif

	<div class="col-lg-6">
		@if (isset($users) && count($users) > 0)
		<div class="bs-component">
			<div class="panel panel-info">

				<div class="panel-heading">
					<h3 class="panel-title">Users
						<a href="#" ><span class='label label-tag pull-right' data-toggle="tooltip" data-placement="bottom"  title="# of Users that match this search term.">{{ count($users)}}</span></a>
					</h3>
				</div>

				<div class="panel-body">
				@include('users.list', ['users' => $users])
				{!! $users->appends(['keyword' => $slug])->links() !!}
				</div>

			</div>
		</div>
		@endif
	
		@if (isset($entities) && count($entities) > 0)
			<div class="bs-component">
				<div class="panel panel-info">

					<div class="panel-heading">
						<h3 class="panel-title">Entities
							<a href="#" ><span class='label label-tag pull-right' data-toggle="tooltip" data-placement="bottom"  title="# of Entities that match this search term.">{{ count($entities)}}</span></a>
						</h3>
					</div>

					<div class="panel-body">
					@include('entities.list', ['entities' => $entities])
					{!! $entities->appends(['keyword' => $slug])->render() !!}
					</div>

				</div>
			</div>
		@endif

		@if (isset($threads) && count($threads) > 0)
			<div class="bs-component">
				<div class="panel panel-info">

					<div class="panel-heading">
						<h3 class="panel-title">Threads
							<a href="#" ><span class='label label-tag pull-right' data-toggle="tooltip" data-placement="bottom"  title="# of Threads that match this search term.">{{ count($threads)}}</span></a>
						</h3>
					</div>

					<div class="panel-body">
						@include('threads.list', ['threads' => $threads])
						{!! $threads->appends(['keyword' => $slug])->render() !!}
					</div>

				</div>
			</div>
		@endif
	</div>
</div>

@stop
 