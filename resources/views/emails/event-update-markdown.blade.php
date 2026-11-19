@php
    // start_at (and so the display_start_at accessor) is nullable — an event
    // can be saved without a date. Resolve it once and guard every format()
    // so a dateless event doesn't crash the follower notification render with
    // "Call to a member function format() on null" (EVENTREPO-ZR).
    $displayStart = $event->display_start_at;
@endphp
@if ($event->cancelled_at)
# {!! $displayStart?->format('l F jS Y') ?? 'Date to be announced' !!}
## ~~{{ $event->name }}~~ **CANCELLED**
@if ($displayStart)
**{!! $displayStart->format('g:i A') !!}**  
@endif
[Link]({{ $url }}events/{{$event->id }})

*This event was cancelled on {{ $event->cancelled_at->format('F jS Y') }}.*

***
@else
# {!! $displayStart?->format('l F jS Y') ?? 'Date to be announced' !!}
@if ($photo = $event->getPrimaryPhoto())
<img src="{{ Storage::disk('external')->url($photo->getStoragePath()) }}">  

@endif
## {{ $event->name }}  
@if ($displayStart)
**{!! $displayStart->format('g:i A') !!}{!! $event->end_time ? ' until '.$event->end_time->format('g:i A') : '' !!}**  
@endif
{{ $event->short ? '*'.$event->short.'*' : '' }}
@if ($dates = $event->showtimesSummary())
Showings: {{ $dates }}  
@endif
[Link]({{ $url }}events/{{$event->id }}) 

@if (!empty($event->series_id))
[{{ $event->series->name }}]({{ $url }}series/{{ strtolower($event->series->slug) }}) series  
@endif

{{ $event->eventType->name }} at  @if ($event->publicVenue())[{{ $event->venue->name }}]({{ $url }}entities/{{ $event->venue->slug }})  
@if ($event->venue->getPrimaryLocationAddress()){{ $event->venue->getPrimaryLocationAddress() }} @endif @elseif ($event->venueLabel()){{ $event->venueLabel() }} @else no venue specified @endif 
@if ($event->door_price)${{ number_format($event->door_price,0) }}@endif 
@if ($event->min_age){{ $event->age_format }}@endif 
@if ($link = $event->primary_link)[Primary Link]({{ $link }})@endif  @if ($ticket = $event->ticket_link)[Buy Ticket]({{ $event->getTicketTrackingLink() }})@endif  

@if ($event->description)
{{ $event->description }}  
@endif

*Added by {{ $event->user->name ?? '' }}*

@unless ($event->entities->isEmpty())
**Related Entities:**
@foreach ($event->entities as $entity)
[{{ $entity->name}}]({{ $url }}events/related-to/{{ $entity->slug }}) 
@endforeach
@endunless

@unless ($event->tags->isEmpty())
**Tags:**
@foreach ($event->tags as $tag)
[{{$tag->name}}]({{ $url }}events/tag/{{ $tag->slug }})
@endforeach
@endunless

***
@endif
