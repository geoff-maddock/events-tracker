@component('mail::message')

Good morning!

@if (count($events) > 0)
Here are the events you are attending today.

### Summary of events:
@foreach ($events as $event)
1. [{{$event->name}}]({{ $url }}events/{{$event->id }})
@endforeach

***

@foreach ($events as $event)
# {!! $event->start_at->format('l F jS Y') !!}
@if ($photo = $event->getPrimaryPhoto())
<img src="{{ asset($photo->getStoragePath()) }}">  

@endif
## {{ $event->name }}  
**{!! $event->start_at->format('h:i A') !!} {!! $event->end_time ? 'until '.$event->end_time->format('h:i A') : '' !!}**  
*{{ $event->short }}* [Link]({{ $url }}events/{{$event->id }}) 

@if (!empty($event->series_id))
[{!! $event->series->name !!}]({{ $url }}series/{{$event->series_id }}) series  
@endif

{{ $event->eventType->name }} at  @if (!empty($event->venue_id))[{!! $event->venue->name !!}]({{ $url }}entities/{{$event->venue->slug }})  
@if ($event->venue->getPrimaryLocationAddress()){{ $event->venue->getPrimaryLocationAddress() }} @endif @else no venue specified @endif 
@if ($event->door_price)${{ number_format($event->door_price,0) }}@endif 
@if ($event->min_age){{ $event->min_age }}@endif 
@if ($link = $event->primary_link)[Primary Link]({{ $link }})@endif  @if ($ticket = $event->ticket_link)[Buy Ticket]({{ $ticket }})@endif  
{{ $event->attendingCount }} users attending  

@if ($event->description)
{{ $event->description }}  
@endif

*Added by {{ $event->user->name ?? '' }}*

@unless ($event->entities->isEmpty())
**Related Entities:**
@foreach ($event->entities as $entity)
[{{ $entity->name}}]({{ $url }}events/relatedto/{{ $entity->slug }}) 
@endforeach
@endunless

@unless ($event->tags->isEmpty())
**Tags:**
@foreach ($event->tags as $tag)
[{{$tag->name}}]({{ $url }}events/tag/{{ $tag->name }})
@endforeach
@endunless

***

@endforeach
@endif

@if (count($seriesList) > 0)
Here are the event series you follow that happen today.

### Summary of series:
@foreach ($seriesList as $series)
1. [{{$series->name}}]({{ $url }}series/{{$series->id }})
@endforeach

@foreach ($seriesList as $series)

# {{ $series->occurrenceType->name }}  {{ $series->occurrence_repeat }}  

@if ($photo = $series->getPrimaryPhoto())
<img src="{{ asset($photo->getStoragePath()) }}">  

@endif
## {{ $series->name }}  
*{{ $series->short }}* [Link]({{ $url }}series/{{$series->id }})  
@if ($series->description)
    {!! $series->description !!}
@endif

{{ $series->eventType->name }} at  @if (!empty($series->venue_id))[{!! $series->venue->name !!}]({{ $url }}entities/{{$series->venue->slug }})  
@if ($series->venue->getPrimaryLocationAddress()){{ $series->venue->getPrimaryLocationAddress() }} @endif @else no venue specified @endif 

@unless ($series->entities->isEmpty())
**Related Entities:**
@foreach ($series->entities as $entity)
[{{ $entity->name}}]({{ $url }}events/relatedto/{{ $entity->slug }}) 
@endforeach
@endunless

@unless ($series->tags->isEmpty())
**Tags:**
@foreach ($series->tags as $tag)
[{{$tag->name}}]({{ $url }}events/tag/{{ $tag->name }})
@endforeach
@endunless

***

@endforeach
@endif

@if (count($interests) > 0)
Here are some events happening today that you might be interested in.  

@foreach ($interests as $tag => $list)

# {{ $tag }}
@if (count($list) == 0) *None listed*  @endif

@foreach ($list as $event)
# {!! $event->start_at->format('l F jS Y') !!}
@if ($photo = $event->getPrimaryPhoto())
<img src="{{ asset($photo->getStoragePath()) }}">  

@endif
## {{ $event->name }}  
**{!! $event->start_at->format('h:i A') !!} {!! $event->end_time ? 'until '.$event->end_time->format('h:i A') : '' !!}**  
*{{ $event->short }}* [Link]({{ $url }}events/{{$event->id }}) 

@if (!empty($event->series_id))
[{!! $event->series->name !!}]({{ $url }}series/{{$event->series_id }}) series  
@endif

{{ $event->eventType->name }} at  @if (!empty($event->venue_id))[{!! $event->venue->name !!}]({{ $url }}entities/{{$event->venue->slug }})  
@if ($event->venue->getPrimaryLocationAddress()){{ $event->venue->getPrimaryLocationAddress() }} @endif @else no venue specified @endif 
@if ($event->door_price)${{ number_format($event->door_price,0) }}@endif 
@if ($event->min_age){{ $event->min_age }}@endif 
@if ($link = $event->primary_link)[Primary Link]({{ $link }})@endif  @if ($ticket = $event->ticket_link)[Buy Ticket]({{ $ticket }})@endif  
{{ $event->attendingCount }} users attending  

@if ($event->description)
{{ $event->description }}  
@endif

*Added by {{ $event->user->name ?? '' }}*

@unless ($event->entities->isEmpty())
**Related Entities:**
@foreach ($event->entities as $entity)
[{{ $entity->name}}]({{ $url }}events/relatedto/{{ $entity->slug }}) 
@endforeach
@endunless

@unless ($event->tags->isEmpty())
**Tags:**
@foreach ($event->tags as $tag)
[{{$tag->name}}]({{ $url }}events/tag/{{ $tag->name }})
@endforeach
@endunless

***

@endforeach
@endforeach
@endif

We're constantly adding new features, functionality and updates to improve your experience.  

If you have any feedback, don't hesitate to [drop us a line](mailto:{{ $admin_email}}).

Thanks!  
{{ $site }}  
{{ $url }}  

<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent