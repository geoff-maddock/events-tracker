<li id="series-{{ $series->id }}" class="series-card" style="clear: both;">
    @if ($primary = $series->getPrimaryPhoto())
        <div style="float: left; padding: 5px;">

            <a href="/{{ $primary->path }}" data-lightbox="{{ $primary->path }}" title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom"><img src="/{{ $primary->thumbnail }}" alt="{{ $series->name}}"  ></a>

        </div>
    @endif
        @if ($series->visibility->name !== 'Public')
            <span class="text-warning">{{ $series->visibility->name }}</span><br>
        @endif
    {!! link_to_route('series.show', $series->name, [$series->id], ['class' => 'item-title', 'alt' => $series->name, 'aria-label' => $series->name]) !!} {{ $series->short }}

    @if ($signedIn && ($series->ownedBy($user) || $user->hasGroup('super_admin')))
        <a href="{!! route('series.edit', ['series' => $series->id],  ['alt' => 'Edit '.$series->name, 'aria-label' => 'Edit '.$series->name]) !!}"><span class='glyphicon glyphicon-pencil'></span></a>
        <a href="{!! route('series.createOccurrence', ['id' => $series->id]) !!}" title="Create the next occurrence of {{ $series->name }}"><span class='glyphicon glyphicon-fire'></span></a>
    @endif

    @if ($signedIn)
        @if ($follow = $series->followedBy($user))
            <a href="{!! route('series.unfollow', ['id' => $series->id]) !!}" data-target="#series-{{ $series->id }}" class="ajax-action"title="Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
        @else
            <a href="{!! route('series.follow', ['id' => $series->id]) !!}" data-target="#series-{{ $series->id }}" class="ajax-action"title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
        @endif

    @endif
    <br>
    {{ $series->occurrenceType->name }}  {{ $series->occurrence_repeat }}
    @if ($series->occurrenceType->name !== 'No Schedule')
        next is
        {{ $series->nextEvent() ? $series->nextEvent()->start_at->format('l F jS Y') : $series->cycleFromFoundedAt()->format('l F jS Y') }}
    @endif

    <br>Founded {!! $series->founded_at ? $series->founded_at->format('l F jS Y') : 'unknown'!!}

    @if ($series->cancelled_at != NULL)
        <br> Cancelled {!! $series->cancelled_at ? $series->cancelled_at->format('l F jS Y') : 'unknown'!!}<br>
    @endif

    @if ($venue = $series->venue)
        <br>
        <a href="/entities/{{urlencode($series->venue->slug)}}">{{ $series->venue->name }}</a> at {{ $series->venue->getPrimaryLocationAddress() }}
    @endunless

    @if ($event = $series->nextEvent())
        <br>Next Event is {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!}
    @endif

    <P>
        @unless ($series->entities->isEmpty())
            @foreach ($series->entities as $entity)
                <span class="label label-tag"><a href="/series/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a></span>
            @endforeach
        @endunless

        @unless ($series->tags->isEmpty())
            @foreach ($series->tags as $tag)
                <span class="label label-tag"><a href="/series/tag/{{ urlencode($tag->name) }}">{{ $tag->name }}</a></span>
            @endforeach
        @endunless
    </P>
</li>
