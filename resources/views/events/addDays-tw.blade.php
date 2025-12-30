	<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS -->
	<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 col-span-full w-full">
		@for ($offset = 0; $offset < 4; $offset++)
		<?php $day = \Carbon\Carbon::parse($date)->addDay($offset); ?>
			<section class="day" data-num="{{ $offset }}" id="day-position-{{ $offset }}" href="/events/day/{{ $day->format('Y-m-d') }}">
				@include('events.day-tw', ['day' => $day, 'position' => $offset ])
			</section>
		@endfor
	</div>

	<div class="block mt-4" id="next-events">
		<div class="w-full">
			<ul class="list-none mt-0">
				<li>{!! link_to_route('events.add', 'Next Events', ['date' => $next_day_window->format('Ymd')], ['id' => 'add-event', 'class' => 'inline-block px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors whitespace-nowrap']) !!}</li>
			</ul>
		</div>
	</div>
