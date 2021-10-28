# {!! $thread->created_at->format('l F jS Y') !!}  

## [{{ $thread->name }}]({{ $url }}threads/{{ $thread->id }})  

**Original Thread** by **{{ $thread->user->name ?? '' }}** at *{!! $thread->created_at->toDayDateTimeString() !!}*  

*{{ $thread->body }}*  

@unless ($thread->series->isEmpty())
**Related Entities:**
@foreach ($thread->series as $s)
[{{ $s->name}}]({{ $url }}threads/related-to/{{ $s->slug }}) 
@endforeach
@endunless

@unless ($thread->tags->isEmpty())
**Tags:**
@foreach ($thread->tags as $tag)
[{{$tag->name}}]({{ $url }}threads/tag/{{ $tag->slug }})
@endforeach
@endunless

***
