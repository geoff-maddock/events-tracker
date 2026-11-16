{{-- Every date of a short-run event (#2375): the next one stands out, past ones are muted. --}}
@if ($event->isMultiDate())
@php
    $occurrences = $event->occurrences();
    $nextOccurrence = $event->nextOccurrence();
    $now = \Carbon\Carbon::now();
@endphp
<div class="text-sm text-muted-foreground" data-showtimes>
	<div class="flex items-center">
		<i class="bi bi-calendar-range mr-2 h-4 w-4"></i>
		<span>{{ $occurrences->count() }} showings</span>
	</div>
	<ul class="mt-1 ml-6 space-y-0.5">
		@foreach ($occurrences as $occurrence)
		@php
		    $isNext = $nextOccurrence !== null && $occurrence->start_at->equalTo($nextOccurrence->start_at);
		    $isPast = $occurrence->start_at->lt($now);
		@endphp
		<li class="{{ $isNext ? 'font-semibold text-foreground' : ($isPast ? 'line-through opacity-60' : '') }}">
			{{ $occurrence->start_at->format('D, M jS') }}
			<span class="mx-1">•</span>
			{{ $occurrence->start_at->format('g:i A') }}
			@if ($isNext)
			<span class="ml-1 text-xs font-normal uppercase tracking-wide">Next</span>
			@endif
			@unless ($isPast)
			<a href="{{ $event->getGoogleCalendarLink($occurrence) }}" target="_blank" rel="nofollow" class="ml-1 hover:text-foreground" title="Add this showing to Google Calendar" aria-label="Add {{ $occurrence->start_at->format('D, M jS') }} to Google Calendar">
				<i class="bi bi-calendar-plus"></i>
			</a>
			@endunless
		</li>
		@endforeach
	</ul>
</div>
@endif
