	<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS -->
	<div class="row small-gutter row-cols-lg-4 home">
		@for ($offset = 0; $offset < 4; $offset++)
		<?php $day = \Carbon\Carbon::parse($date)->addDay($offset); ?>
			<section class="day" data-num="{{ $offset }}" id="day-position-{{ $offset }}" href="/events/day/{{ $day->format('Y-m-d') }}">
				@include('events.day', ['day' => $day, 'position' => $offset ])
			</section>
		@endfor
	</div>

	<div class="d-block" id="next-events">
		<div class="col-sm-12">
			<ul class="list-style-none"  class="mt-0">
				<li>{!! link_to_route('events.add', 'Next Events', ['date' => $next_day_window->format('Ymd')], ['id' => 'add-event', 'class' => 'next-events page-link text-nowrap']) !!}</li>
			</ul>
		</div>
	</div>
	