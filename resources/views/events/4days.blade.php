<div class="row">
	<div class="col-sm-3">
	<ul class="pagination float-start" class="mt-0">
		<li>{!! link_to_route('events.upcoming', '< Past Week', ['date' => $prev_day_window->format('Ymd')], ['class' => 'item-title text-nowrap']) !!}</li>
		<li>{!! link_to_route('events.upcoming', '< Past Day', ['date' => $prev_day->format('Ymd')], ['class' => 'item-title text-nowrap']) !!}</li>
	</ul>
	</div>
	
	<div class="col-sm-6">
	<ul class="pagination" class="mt-0">
	<li></li>
	</ul>
	</div>
	
	<div class="col-sm-3">
	<ul class="pagination float-end"  class="mt-0">
		<li>{!! link_to_route('events.upcoming', 'Future Day >', ['date' => $next_day->format('Ymd')], ['class' => 'item-title text-nowrap']) !!}</li>
		<li>{!! link_to_route('events.upcoming', 'Future Week >', ['date' => $next_day_window->format('Ymd')], ['class' => 'item-title text-nowrap']) !!}</li>
	</ul>
	</div>
	</div>
	<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS -->
	
	<div class="row small-gutter">
		@for ($offset = 0; $offset < 4; $offset++)
		<?php $day = \Carbon\Carbon::parse($date)->addDay($offset); ?>
			<section class="day" data-num="{{ $offset }}" id="day-position-{{ $offset }}" href="/events/day/{{ $day->format('Y-m-d') }}">
				@include('events.day', ['day' => $day, 'position' => $offset ])
			</section>
		@endfor
	</div>
	