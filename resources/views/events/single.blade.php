<li id="event-{{ $event->id }}" class="event-card" style="clear: both;">
		@if ($primary = $event->getPrimaryPhoto())
		<div style="float: left; padding: 5px;">
			<a href="/{{ $primary->path }}" data-lightbox="{{ $primary->path }}" title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom"><img src="/{{ $primary->thumbnail }}" alt="{{ $event->name}}"  ></a>
		</div>
		@endif

		@if ($event->visibility->name !== 'Public')
			<span class="text-warning">{{ $event->visibility->name }}</span><br>
		@endif
		<div class='event-date'>{!! $event->start_at->format('D F jS Y') !!} </div>

			{!! link_to_route('events.show', $event->name, [$event->id], ['class' => 'item-title', 'alt' => $event->name, 'aria-label' => $event->name]) !!}

			@if ($signedIn && ($event->ownedBy($user) || $user->hasGroup('super_admin')))
				<a href="{!! route('events.edit', ['id' => $event->id],  ['alt' => 'Edit '.$event->name, 'aria-label' => 'Edit '.$event->name]) !!}" title="Edit this event."><span class='glyphicon glyphicon-pencil'></span></a>
			@endif

			@if ($thread = $event->threads->first())
				<a href="{!! route('threads.show', ['id' => $thread->id]) !!}" title="Show the related thread."><span class='glyphicon glyphicon-comment'></span></a>
			@endif


			@if ($link = $event->primary_link)
				<a href="{{ $link }}" title="External link for this event" target="_blank" rel="noopener"><span class='glyphicon glyphicon-link'></span></a>
			@endif

			@if ($ticket = $event->ticket_link)
				<a href="{{ $ticket }}" target="_" title="Ticket link"><span class='glyphicon glyphicon-shopping-cart'></span></a>
			@endif

			@if ($signedIn)
				@if ($response = $event->getEventResponse($user))
				<a href="{!! route('events.unattend', ['id' => $event->id]) !!}" data-target="#event-{{ $event->id }}" class="ajax-action" title="{{ $response->responseType->name }}"><span class='glyphicon glyphicon-star text-warning'></span></a>
				@else
				<a href="{!! route('events.attend', ['id' => $event->id]) !!}" data-target="#event-{{ $event->id }}" class="ajax-action" title="Click star to mark attending"><span class='glyphicon glyphicon-star-empty text-info'></span></a>
				@endif
			@endif

		<br>
		@if (!empty($event->series_id))
		<a href="/series/{{$event->series_id }}">{!! $event->series->name !!}</a> series
		@endif

		<a href="/events/type/{{ urlencode($event->eventType->name) }}">{{ $event->eventType->name }}</a>

		@if ($event->venue)
		<br><a href="/entities/{{$event->venue->slug }}">{{ $event->venue->name}}</a>
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

		<P>
		@unless ($event->entities->isEmpty())
			@foreach ($event->entities as $entity)
				<span class="label label-tag"><a href="/events/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a>
					<a href="{!! route('entities.show', ['id' => $entity->slug]) !!}" title="Show this entity."><span class='glyphicon glyphicon-link text-info'></span></a>
				</span>
			@endforeach
		@endunless

		@unless ($event->tags->isEmpty())
			@foreach ($event->tags as $tag)
					<span class="label label-tag"><a href="/events/tag/{{ urlencode($tag->name) }}" class="label-link">{{ $tag->name }}</a>
                        <a href="{!! route('tags.show', ['slug' => $tag->name]) !!}" title="Show this tag."><span class='glyphicon glyphicon-link text-info'></span></a>
                    </span>
			@endforeach
		@endunless
		</P>

</li>
