<div class="row">
	<div class="col-sm-3">
	<ul class="pagination pull-left" style="margin-top: 0px;">
		<li>{!! link_to_route('events.upcoming', '< Past Week', ['date' => $prev_day_window->format('Ymd')], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
		<li>{!! link_to_route('events.upcoming', '< Past Day', ['date' => $prev_day->format('Ymd')], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
	</ul>
	</div>
	
	<div class="col-sm-6">
	<ul class="pagination" style="margin-top: 0px;">
	<li></li>
	</ul>
	</div>
	
	<div class="col-sm-3">
	<ul class="pagination pull-right" style="margin-top: 0px;">
		<li>{!! link_to_route('events.upcoming', 'Future Day >', ['date' => $next_day->format('Ymd')], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
		<li>{!! link_to_route('events.upcoming', 'Future Week >', ['date' => $next_day_window->format('Ymd')], ['class' => 'item-title', 'style' => 'white-space: nowrap;']) !!}</li>
	</ul>
	</div>
	</div>
	<br style="clear: left;"/>
	<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS -->
	
	<div class="row small-gutter">
		@for ($offset = 0; $offset < 4; $offset++)
		<?php $day = \Carbon\Carbon::parse($date)->addDay($offset); ?>
			<section class="day" data-num="{{ $offset }}" id="day-position-{{ $offset }}" href="/events/day/{{ $day->format('Y-m-d') }}">
				@include('events.day', ['day' => $day, 'position' => $offset ])
			</section>
		@endfor
	</div>
	