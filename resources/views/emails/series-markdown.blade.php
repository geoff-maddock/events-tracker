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
