@extends('app')

@section('title','Events')

@section('content')

	<h4>Events
		@include('events.crumbs')
	</h4>

	<p>
	<a href="{{ url('/events/all') }}" class="btn btn-info">Show all events</a>
	<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show paginated events</a>
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a>	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	</p>

	<br style="clear: left;"/>

	<div class="row">

	@if (isset($events) && count($events) > 0)
	<div class="col-lg-6">
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

	@if (isset($past_events) && count($past_events) > 0)
	<div class="col-lg-6">
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title"><a href="{{ url('/events/past') }}">Past Events</a></h3>
				</div>

				<div class="panel-body">
				@include('events.list', ['events' => $past_events])
				{!! $past_events->render() !!}
				</div>

			</div>
		</div>
	</div>
	@endif
	
	@if (isset($future_events) && count($future_events) > 0)	
	<div class="col-lg-6">
		<div class="bs-component">
			<div class="panel panel-info">

			
				<div class="panel-heading">
					<h3 class="panel-title"><a href="{{ url('/events/future') }}">Future Events</a></h3>
				</div>

				<div class="panel-body">
				@include('events.list', ['events' => $future_events])
				{!! $future_events->render() !!}
				</div>

			</div>
		</div>
	</div>
	@endif
	</div>

@stop
 