<li id="series-{{ $series->id }}" class="series-card">
    @if ($primary = $series->getPrimaryPhoto())
    <div class="event-list-thumbnail">
		<a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}" 
			data-title="{{ $series->occurrenceType->name }}  {{ $series->occurrence_repeat }}  <a href='/series/{{ $series->id }}'>{{ $series->name }}</a> @ <a href='/entities/{{ $series->venue ? $series->venue->slug : '' }}'>{{ $series->venue ? $series->venue->name : '' }}</a>"
			data-lightbox="{{ $primary->path }}"
            title="Click to see enlarged image."
			data-toggle="tooltip" data-placement="bottom">
			<img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}" alt="{{ $series->name }}" class="thumbnail-image">
		</a>

    </div>
    @else
    <div class="event-list-thumbnail">

        <a href="/images/event-placeholder.png" 
            data-title="{{ $series->occurrenceType->name }}  {{ $series->occurrence_repeat }}  <a href='/series/{{ $series->id }}'>{{ $series->name }}</a> @ <a href='/entities/{{ $series->venue ? $series->venue->slug : '' }}'>{{ $series->venue ? $series->venue->name : '' }}</a>"
            data-lightbox="/images/event-placeholder.png"
            title="Click to see enlarged image."
            data-toggle="tooltip" data-placement="bottom">
            <img src="/images/event-placeholder.png" alt="{{ $series->name }}" class="thumbnail-image">
        </a>

    </div>

    @endif

    <span class="series-occurrence">{{ $series->occurrenceType->name }} {{ $series->occurrence_repeat }}</span>

    @if ($signedIn && ($series->ownedBy($user) || $user->hasGroup('super_admin')))
    <a href="{!! route('series.edit', ['series' => $series->id]) !!}"
        class="card-actions mx-1" alt="Edit {{ $series->name}}" aria-label="Edit {{ $series->name }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-fill" viewBox="0 0 16 16">
            <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207l6.5-6.5zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.499.499 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11l.178-.178z"/>
          </svg>
    </a>

    <a href="{!! route('series.createOccurrence', ['id' => $series->id]) !!}"
        title="Create the next occurrence of {{ $series->name }}" class="card-actions mx-1" alt="Create Occurrence {{ $series->name}}" aria-label="Create Occurence {{ $series->name }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-plus" viewBox="0 0 16 16">
            <path d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7z"/>
            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
          </svg>

        </a>
    @endif

    @if ($signedIn)
    @if ($follow = $series->followedBy($user))
    <a href="{!! route('series.unfollow', ['id' => $series->id]) !!}" data-target="#series-{{ $series->id }}"
        class="ajax-action card-actions mx-1" title="Click to unfollow" alt="Unfollow {{ $series->name}}" aria-label="Unfollow {{ $series->name }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dash-circle-fill" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM4.5 7.5a.5.5 0 0 0 0 1h7a.5.5 0 0 0 0-1h-7z"/>
          </svg>
    </a>
    @else
    <a href="{!! route('series.follow', ['id' => $series->id]) !!}" data-target="#series-{{ $series->id }}"
        class="ajax-action card-actions mx-1" title="Click to follow" alt="Follow {{ $series->name}}" aria-label="Follow {{ $series->name }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3v-3z"/>
          </svg>
        </a>
    @endif

    @endif
    <div class='event-time'>
        @if ($series->occurrenceType->name !== 'No Schedule')
        Next is
        {{ $series->nextEvent() ? $series->nextEvent()->start_at->format('l F jS Y') :
        $series->cycleFromFoundedAt()->format('l F jS Y') }}
        @endif
	</div>
    <div>
    @if ($series->visibility->name !== 'Public')
    <span class="text-warning">{{ $series->visibility->name }}</span>
    @endif

    {!! link_to_route('series.show', $series->name, [$series->id], ['class' => 'item-title', 'alt' => $series->name, 'aria-label' => $series->name]) !!}
    </div>


    <small>
         {{ $series->short }}
    </small>

    @if ($series->cancelled_at != NULL)
    <br> Cancelled {!! $series->cancelled_at ? $series->cancelled_at->format('l F jS Y') : 'unknown'!!}<br>
    @endif

    @if ($venue = $series->venue)
    <br>
        <a href="/entities/{{urlencode($series->venue->slug)}}">{{ $series->venue?->name }}</a>
        @if ($series->venue?->getPrimaryLocationAddress() )
        @if ($series->venue?->getPrimaryLocationMap() != '')
        <a href="{!! $series->venue->getPrimaryLocationMap() !!}" target="_" title="{{ $series->venue?->getPrimaryLocationAddress()}}" class="mx-1">
            <i class="bi bi-geo-alt-fill"></i>
        </a>
        @else
        <a href="#" title="{{ $series->venue?->getPrimaryLocationAddress()}}" class="mx-1">
            <i class="bi bi-geo-alt-fill"></i>
        </a>
        @endif
        @endif
    @endif


    
    @if ($event = $series->nextEvent())
    <br>Next Event is {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!}
    @endif

    <P>
        @unless ($series->entities->isEmpty())
        @foreach ($series->entities as $entity)
            @include('entities.single_label')
        @endforeach
        @php unset($entity) @endphp
        @endunless

        @unless ($series->tags->isEmpty())
        @foreach ($series->tags as $tag)
            @include('tags.single_label')
        @endforeach
        @endunless
    </P>

	@include('embeds.minimal-playlist', ['series' => $series])
</li>