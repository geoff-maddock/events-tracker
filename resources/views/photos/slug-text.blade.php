@if ($event = $photo->events->first())
{!! $event->start_at->format('m / d / y') !!} {{ $event->name }} @ {{ $event->venue ? $event->venue->slug : '' }} {{ $event->venueLabel() ?? '' }}
@else 
{{ $photo->name }}
@endif 