# {!! $event->start_at->format('l F jS Y') !!}
@if ($photo = $event->getPrimaryPhoto())
<img src="{{ Storage::disk('external')->url($photo->getStoragePath()) }}">  

@endif
## {{ $event->name }}  
**{!! $event->start_at->format('g:i A') !!}{!! $event->end_time ? ' until '.$event->end_time->format('g:i A') : '' !!}**  
{!! $event->short ? '*'.$event->short.'*' : '' !!}
[Link]({{ $url }}events/{{$event->id }}) 

@if (!empty($event->series_id))
[{!! $event->series->name !!}]({{ $url }}series/{{$event->series_id }}) series  
@endif

{{ $event->eventType->name }} at  @if (!empty($event->venue_id))[{!! $event->venue->name !!}]({{ $url }}entities/{{$event->venue->slug }})  
@if ($event->venue->getPrimaryLocationAddress()){{ $event->venue->getPrimaryLocationAddress() }} @endif @else no venue specified @endif 
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
