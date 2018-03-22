@extends('app')

@section('title','Events')

@section('content')

	<h4>Week's Events
		@include('events.crumbs')
	</h4>

	<div class="row">
		<div class="col-sm-9">
			<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show event index</a>
			<a href="{!! URL::route('calendar') !!}" class="btn btn-info">Show calendar</a>
			<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a>	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
		</div>
	</div>

	<br style="clear: left;"/>

	<!-- DISPLAY THE NEXT SIX DAYS OF EVENTS -->
	<?php 	$today = \Carbon\Carbon::now(); ?>
	<div class="row">

		@for ($i = 0; $i < 6; $i++) 

		<?php $day = \Carbon\Carbon::parse($today)->addDay($i);?>
		<div class="col-md-2">
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

					<div class="panel-body week-text">
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
 