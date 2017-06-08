<div class="col-lg-3">
	<div class="bs-component">
		<div class="panel panel-info">

			<div class="panel-heading">
				<h3 class="panel-title">
				@if ($offset == 0) 
				Today's Events
				@else
				{{ $day->format('l M jS Y') }}
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