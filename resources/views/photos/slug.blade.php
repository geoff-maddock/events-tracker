@if ($event = $photo->events->first())
{!! $event->start_at->format('m / d / y') !!} <a href='/events/{{ $event->id }}'>{{ $event->name }}</a> @ <a href='/entities/{{ $event->venue ? $event->venue->slug : '' }}'>{{ $event->venueLabel() ?? '' }}</a>
@else 
{{ $photo->name }}
@endif 