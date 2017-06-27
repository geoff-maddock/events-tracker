@extends('app')

@section('title','Events')

@section('content')

	<h1>Search
		@include('events.crumbs')
	</h1>

	<p>
	<a href="{{ url('/events/all') }}" class="btn btn-info">Show all events</a>
	<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show paginated events</a>
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a>	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	</p>

	<br style="clear: left;"/>

	<div class="row">

	@if (isset($events) && count($events) > 0)
	<div class="col-lg-6">

		@if (isset($tags) && count($tags) > 0)
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Tags</h3>
				</div>

				<div class="panel-body">
				@include('tags.list', ['tags' => $tags])
				{!! $series->render() !!}
				</div>

			</div>
		</div>
		@endif

		@if (isset($series) && count($series) > 0)
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Series</h3>
				</div>

				<div class="panel-body">
				@include('series.list', ['series' => $series])
				{!! $series->render() !!}
				</div>

			</div>
		</div>
		@endif

		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Events</h3>
				</div>

				<div class="panel-body">
				@include('events.list', ['events' => $events])
				{!! $events->render() !!}
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
					<h3 class="panel-title">Users</h3>
				</div>

				<div class="panel-body">
				@include('users.list', ['users' => $users])
				{!! $series->render() !!}
				</div>

			</div>
		</div>
		@endif
	
	@if (isset($entities) && count($entities) > 0)	

		<div class="bs-component">
			<div class="panel panel-info">
			
				<div class="panel-heading">
					<h3 class="panel-title">Entities</h3>
				</div>

				<div class="panel-body">
				@include('entities.list', ['entities' => $entities])
				{!! $entities->render() !!}
				</div>

			</div>
		</div>

	@endif
	</div>
</div>

@stop
 