<html>
<body>
<div>
    Hello {{ $user->name }},<br><br>
    We're checking in from <a href="{{ $url }}">{{ $url }}</a> with an update on the site.<br><br>
    Since you last visited, numerous new shows, club nights, venues, promoters and artists have been added.<br>
    Log back in to see what's going on around town, to follow your favorites and get automatic updates via<br>
    email and to join in on the conversation by adding content or posting on the forum.<br><br>

    @if (count($events) > 0)
        <h3>Here's some updates on who you are following:</h3>
        @foreach ($events as $entity => $list)
            <h2>{{ $entity }}</h2>
        @foreach ($list as $event)
           <div>
               {!! $event->start_at->format('l F jS Y') !!} <br>
               <b><a href="{{ $url }}/events/{{ $event->id }}">{{ $event->name }}</a></b><br>
               <i>{{ $event->short }}</i>

               @if (!empty($event->series_id))
                   <br><a href="/series/{{$event->series_id }}">{!! $event->series->name !!}</a> series
               @endif
               <br>
               {{ $event->eventType->name }}

               @if ($event->venue)
                   <br>{{ $event->venue->name or 'No venue specified' }}
                   @if ($event->venue->getPrimaryLocationAddress() )
                       {{ $event->venue->getPrimaryLocationAddress() }}
                   @endif
               @else
                   no venue specified
               @endif

               @if ($event->start_at)
                   at {{ $event->start_at->format('g:i A') }}
               @endif

               @if ($event->door_price)
                   ${{ number_format($event->door_price,0) }}
               @endif


               @unless ($event->entities->isEmpty())
                   <br>
                   Related:
                   @foreach ($event->entities as $entity)
                       {{ $entity->name }},
                   @endforeach
               @endunless

               @unless ($event->tags->isEmpty())
                   Tags:
                   @foreach ($event->tags as $tag)
                       {{ $tag->name }},
                   @endforeach
               @endunless

               @if ($event->primary_link)
                   <br>{{ $event->primary_link or ''}}
               @endif
                <br>
           </div>
        @endforeach
    @endforeach
    <br><br>
    @endif
    We're constantly adding new features, functionality and updates to improve your experience. <br>
    If you have any feedback, don't hesitate to drop us a line.
</div>

<P></P>
Thanks!<br>
{{ $site }}<br>
{{ $url }}
</body>
</html>