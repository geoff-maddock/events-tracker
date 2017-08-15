<li class="event-card" style="clear: both;">

		@if ($primary = $event->getPrimaryPhoto())
		<div style="float: left; padding: 5px;">
		<a href="/{{ $event->getPrimaryPhoto()->path }}" data-lightbox="{{ $event->getPrimaryPhoto()->path }}"><img src="/{{ $event->getPrimaryPhoto()->thumbnail }}" alt="{{ $event->name}}"  style="max-width: 100px; "></a>
		</div>
		@endif

		@if ($month != $event->start_at->format('F')) 
			<?php $month = $event->start_at->format('F')?>
		@endif

		<div class='event-date'>{!! $event->start_at->format('l F jS Y') !!} </div>
	
			{!! link_to_route('events.show', $event->name, [$event->id], ['class' => 'item-title']) !!} 
	
			@if ($signedIn && $event->ownedBy($user))
			<a href="{!! route('events.edit', ['id' => $event->id]) !!}" title="Edit this event."><span class='glyphicon glyphicon-pencil'></span></a>
			@endif

			@if ($link = $event->primary_link)
				<a href="{{ $link }}" title="External link for this event" target="_blank"><span class='glyphicon glyphicon-link'></span></a>
			@endif
			
			@if ($ticket = $event->ticket_link)
				<a href="{{ $ticket }}" target="_" title="Ticket link"><span class='glyphicon glyphicon-shopping-cart'></span></a>
			@endif

			@if ($signedIn)
				@if ($response = $event->getEventResponse($user))
				<a href="{!! route('events.unattend', ['id' => $event->id]) !!}" title="{{ $response->responseType->name }}"><span class='glyphicon glyphicon-star text-warning'></span></a>
				@else
				<a href="{!! route('events.attend', ['id' => $event->id]) !!}" title="Click star to mark attending"><span class='glyphicon glyphicon-star text-info'></span></a>
				@endif
			@endif

		<br>
		@if (!empty($event->series_id))
		<a href="/series/{{$event->series_id }}">{!! $event->series->name !!}</a> series
		@endif

		<a href="/events/type/{{ urlencode($event->eventType->name) }}">{{ $event->eventType->name }}</a>

		@if ($event->venue)
		<br><a href="/entities/{{$event->venue->id }}">{{ $event->venue->name or 'No venue specified' }}</a>
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
		Related:
			@foreach ($event->entities as $entity)
				<span class="label label-tag"><a href="/events/relatedto/{{ urlencode($entity->slug) }}">{{ $entity->name }}</a></span>
			@endforeach
		@endunless

		@unless ($event->tags->isEmpty())
		Tags:
			@foreach ($event->tags as $tag)
				<span class="label label-tag"><a href="/events/tag/{{ urlencode($tag->name) }}">{{ $tag->name }}</a></span>
			@endforeach
		@endunless
		</P>

</li>