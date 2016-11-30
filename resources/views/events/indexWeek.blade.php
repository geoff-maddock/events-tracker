@extends('app')

@section('title','Events')

@section('content')

	<h1>Events
		@include('events.crumbs')
	</h1>

	<p>
	<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a>	<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	</p>

	<br style="clear: left;"/>
	
	<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS --> 
	<?php 	$today = \Carbon\Carbon::now(); ?>
	<div class="row">

		@for ($i = 0; $i < 6; $i++) 

		<?php $day = \Carbon\Carbon::parse($today)->addDay($i);?>
		<div class="col-lg-2">
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

					</div>
				</div>
			</div>
		</div>
		@endfor

	</div>


@stop
 