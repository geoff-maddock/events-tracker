<li id="event-{{ $event->id }}" class="event-card {{ $event->pastOrFuture }}" style="display: flow-root;">
	@if ($primary = $event->getPrimaryPhoto())
	<div class="event-list-thumbnail">
		<a href="{{ $primary->getStoragePath() }}" data-lightbox="{{ $primary->getStoragePath() }}"
			title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom">
			<img src="{{ $primary->getStorageThumbnail() }}" alt="{{ $event->name}}" class="thumbnail-image">
		</a>
	</div>
	@else
	<div class="event-list-thumbnail">
		<a href="/images/event-placeholder.png" data-lightbox="/images/event-placeholder.png"
			title="Click to see enlarged image" data-toggle="tooltip" data-placement="bottom">
			<img src="/images/event-placeholder.png" alt="{{ $event->name}}" class="thumbnail-image">
		</a>
	</div>
	@endif

	@if ($event->visibility->name !== 'Public')
	<span class="text-warning">{{ $event->visibility->name }}</span><br>
	@endif
	<span class='event-date'>{!! $event->start_at->format('D F jS Y') !!} </span>

	<!-- ACTIONS -->
	<span>
		@if ($signedIn && ($event->ownedBy($user) || $user->hasGroup('super_admin')))
		<a href="{!! route('events.edit', ['event' => $event->id],  ['alt' => 'Edit '.$event->name, 'aria-label' => 'Edit '.$event->name]) !!}"
			title="Edit this event.">
			<i class="bi bi-pencil-fill card-actions"></i>
		</a>
		@endif

		@if ($thread = $event->threads->first())
		<a href="{!! route('threads.show', ['thread' => $thread->id]) !!}" title="Show the related thread.">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-chat-fill card-actions" viewBox="0 0 16 16">
				<path d="M8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6-.097 1.016-.417 2.13-.771 2.966-.079.186.074.394.273.362 2.256-.37 3.597-.938 4.18-1.234A9.06 9.06 0 0 0 8 15z"/>
			  </svg>
		</a>
		@endif


		@if ($link = $event->primary_link)
		<a href="{{ $link }}" title="External link for this event" target="_blank" rel="noopener">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-link-45deg card-actions" viewBox="0 0 16 16">
				<path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1.002 1.002 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4.018 4.018 0 0 1-.128-1.287z"/>
				<path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 1 0-4.243-4.243L6.586 4.672z"/>
			  </svg>
		</a>
		@endif

		@if ($ticket = $event->ticket_link)
		<a href="{{ $ticket }}" target="_" title="Ticket link">
			<i class="bi bi-cart-fill card-actions"></i>	
		</a>
		@endif

		@if ($signedIn)
		@if ($response = $event->getEventResponse($user))
		<a href="{!! route('events.unattend', ['id' => $event->id]) !!}" data-target="#event-{{ $event->id }}" class="ajax-action" title="{{ $response->responseType->name }}">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-star-fill card-actions" viewBox="0 0 16 16">
				<path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
			  </svg>
			</a>
		@else
		<a href="{!! route('events.attend', ['id' => $event->id]) !!}" data-target="#event-{{ $event->id }}" class="ajax-action" title="Click star to mark attending">
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-star card-actions" viewBox="0 0 16 16">
				<path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z"/>
			  </svg>
		</a>
		@endif
		@endif
	</span>
	<!-- END ACTIONS-->
	@if ($event->start_at)
	<div class='event-time'>{{ $event->start_at->format('g:i A') }}
		@if ($event->end_at)
			- {{ $event->end_at->format('g:i A') }}
		@endif
	</div>
	@endif
	<div>
		{!! link_to_route('events.show', $event->name, [$event->id], ['class' => 'item-title', 'alt' =>
		$event->name,
		'aria-label' => $event->name]) !!}
		<br>
		@if (!empty($event->series_id))
		<a href="/series/{{$event->series_id }}">{!! $event->series->name !!}</a> series
		@endif

		<a href="/events/type/{{ urlencode($event->eventType->name) }}">{{ $event->eventType->name }}</a>

		@if ($event->venue)
		at <a href="/entities/{{$event->venue->slug }}">{{ $event->venue->name}}</a>
		@if ($event->venue->getPrimaryLocationAddress() )
		{{ $event->venue->getPrimaryLocationAddress() }}
		@endif
		@else
		no venue specified
		@endif



		@if ($event->door_price)
		${{ number_format($event->door_price,0) }}
		@endif

		<P>
			@unless ($event->entities->isEmpty())
			@foreach ($event->entities as $entity)
				@include('entities.single_label')
			@endforeach
			@endunless

			@unless ($event->tags->isEmpty())
			@foreach ($event->tags as $tag)
				@include('tags.single_label')
			@endforeach
			@endunless
		</P>
	</div>
</li>