<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS -->
<div class="home grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 w-full">
	@for ($offset = 0; $offset < 4; $offset++)
	<?php $day = \Carbon\Carbon::parse($date)->addDay($offset); ?>
		<section class="day min-h-[500px]" data-num="{{ $offset }}" id="day-position-{{ $offset }}" href="/events/day/{{ $day->format('Y-m-d') }}">
			@include('events.day-tw', ['day' => $day, 'position' => $offset ])
		</section>
	@endfor
</div>

<!-- Next Events Button -->
<div class="flex justify-center mt-6" id="next-events">
	{!! link_to_route('events.add', 'Load Next Events', ['date' => $next_day_window->format('Ymd')], ['id' => 'add-event', 'class' => 'px-6 py-3 bg-accent text-foreground border-2 border-primary font-semibold rounded-lg hover:bg-accent/80 transition-colors shadow-lg next-events whitespace-nowrap']) !!}
</div>
