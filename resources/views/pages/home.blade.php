@extends('app')

@section('title','Home')

@section('content')

	<div class="jumbotron">
	<h3>Event Repo</h3>
	<p>A guide and calander of events, weekly and  monthly series, promoters, artists, producers, djs, venues and other entities.</p>
	<P>
	<a href="{{ url('/events/all') }}" class="btn btn-info">Show all events</a>
	<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show paginated events</a>
	<a href="{!! URL::route('series.index') !!}" class="btn btn-info">Show event series</a> 
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a> 
	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	<a href="{!! URL::route('entities.create') !!}" class="btn btn-primary">Add an entity</a>
	</p>

	</div>
	<br style="clear: left;"/>

 
	<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS --> 
	<?php 	$today = \Carbon\Carbon::now('America/New_York'); ?>
	<div class="row">
 
		@for ($i = 0; $i < 4; $i++) 

		<?php $day = \Carbon\Carbon::parse($today)->addDay($i);?>
		<div class="col-lg-3">
			<div class="bs-component">
				<div class="panel panel-info">

					<div class="panel-heading">
						<h3 class="panel-title">@if ($i == 0) 
						Today's Events
						@else
						{{ $day->format('l') }}
						@endif
						</h3>
					</div>

					<div class="panel-body">
					<?php $events = App\Event::starting($day->format('Y-m-d'))->get();	?>
					@include('events.list', ['events' => $events])

					<!-- find all series that would fall on this date -->
					<?php $series = App\Series::byNextDate($day->format('Y-m-d'));?>
					@include('series.list', ['series' => $series])

					</div>
				</div>
			</div>
		</div>
		@endfor

	</div>
@stop