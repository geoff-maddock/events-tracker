@extends('app')

@section('title','This Week\'s Events')

@section('content')

<h1 class="display-crumbs text-primary">Week's Events
	@include('events.crumbs')
</h1>


<div class="row">
	<div id="action-menu" class="mb-2">
		<a href="{!! URL::route('events.index') !!}" class="btn btn-info">Show event index</a>
		<a href="{!! URL::route('calendar') !!}" class="btn btn-info">Show calendar</a>
		<a href="{!! URL::route('events.create') !!}" class="btn btn-primary">Add an event</a> 
		<a href="{!! URL::route('series.create') !!}" class="btn btn-primary">Add an event series</a>
	</div>
</div>

<!-- DISPLAY THE NEXT SIX DAYS OF EVENTS -->
<?php 	$today = \Carbon\Carbon::now(); ?>
<div class="row gx-2">

	@for ($i = 0; $i < 6; $i++) <?php $day = \Carbon\Carbon::parse($today)->addDay($i); ?>
		<div class="col">
			<div class="card surface mb-2">
				<div class="card-header bg-primary">

					<h5 class="my-0 fw-normal">
						@if ($i == 0)
							Today's Events
						@else
						{{ $day->format('l') }}
						@endif
					</h5>
				</div>
				<div class="card-body week-text">
					<?php $events = App\Models\Event::starting($day->format('Y-m-d'))->get(); ?>
					@if (count($events) > 0)

					<ul class='day-list'>
						<?php $month = '';?>
						@foreach ($events as $event)
									@include('events.single', ['event' => $event])
						@endforeach
					</ul>
					
					@else
						<div><small>No events listed today.</small></div>
					@endif

					<!-- find all series that would fall on this date -->
					<?php $series = App\Models\Series::byNextDate($day->format('Y-m-d')); ?>
					@if (count($series) > 0)
					<ul class='day-list'>
					
						@php $type = NULL @endphp
					
						@foreach ($series as $s)
							@if ($type !== $s->occurrence_type_id)
								<li>
									<h4>{{ $s->occurrenceType->name }}</h3>
									<?php $type = $s->occurrence_type_id; ?>
								</li>
							@endif
							@include('series.single', ['series' => $s])
						@endforeach
					</ul>
					@else
					<div><small>No series listed today.</small></div>
					@endif
				</div>
			</div>
		</div>
		@endfor

</div>


@stop