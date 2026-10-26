<!-- DISPLAY THE NEXT FOUR DAYS OF EVENTS -->
<div class="home grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 w-full">
	<?php
	// Load the whole 4-day window in a single query and group by day, so each
	// day-tw partial doesn't issue its own query (N+1, EVENTREPO-Z4).
	$windowStart = \Carbon\Carbon::parse($date);
	$eventsByDay = App\Models\Event::with([
		'series.occurrenceType',
		'series.occurrenceWeek',
		'series.occurrenceDay',
		'series.visibility',
		'visibility',
		'venue',
		'entities',
		'tags',
		'threads',
		// only the viewer's response is shown on the card
		'eventResponses' => fn ($q) => $q->where('user_id', auth()->id()),
		'photos'
	])->startingBetween($windowStart->format('Y-m-d'), $windowStart->copy()->addDay(3)->format('Y-m-d'))
		->get()
		->groupBy(fn ($event) => $event->start_at->format('Y-m-d'));
	?>
	@for ($offset = 0; $offset < 4; $offset++)
	<?php $day = \Carbon\Carbon::parse($date)->addDay($offset); ?>
		<section class="day min-h-[500px]" data-num="{{ $offset }}" id="day-position-{{ $offset }}" href="/events/day/{{ $day->format('Y-m-d') }}">
			@include('events.day-tw', ['day' => $day, 'position' => $offset, 'events' => $eventsByDay->get($day->format('Y-m-d'), collect()) ])
		</section>
	@endfor
</div>

<!-- Next Events Button -->
@if ($hasNextWindow ?? true)
<div class="flex justify-center mt-6" id="next-events">
	<a href="{{ route('events.add', ['date' => $next_day_window->format('Ymd')]) }}" id="add-event" class="px-6 py-3 bg-accent text-foreground border-2 border-primary font-semibold rounded-lg hover:bg-accent/80 transition-colors shadow-lg next-events whitespace-nowrap">Load Next Events</a>
</div>
@endif
