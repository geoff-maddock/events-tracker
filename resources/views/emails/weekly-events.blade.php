<html>
<body>
<div>
Here is a reminder from <a href="{{ $url }}">{{ $url }}</a> about these upcoming events that you are attending.<br>

@if (count($events) > 0)
@foreach ($events as $event)
<div class='event-date'>
	<h2>{!! $event->start_at->format('l F jS Y') !!}</h2>

	{!! $event->start_at->format('h:i A') !!} {!! $event->end_time ? 'until '.$event->end_time->format('h:i A') : '' !!}
	</div>

	<h2><a href="{{ $url }}events/{{$event->id }}">{{ $event->name }}</h2>
	<i>{{ $event->short }}</i><br>

	<b>
	@if (!empty($event->series_id))
	<a href="{{ $url }}/series/{{$event->series_id }}">{!! $event->series->name !!}</a> series
	@endif

	<a href="{{ $url }}/events/type/{{$event->eventType->name }}">{{ $event->eventType->name }}</a>
	<br>

	@if (!empty($event->venue_id))
	<a href="{{ $url }}/entities/{{$event->venue->slug }}">{!! $event->venue->name !!}</a>

	@if ($event->venue->getPrimaryLocationAddress() )
		{{ $event->venue->getPrimaryLocationAddress() }}
	@endif
	@else
	no venue specified
	@endif
	</b>

	@if ($event->door_price)
	${{ number_format($event->door_price,0) }}
	@endif

 	@if ($event->min_age)
	{{ $event->min_age }}
	@endif

	<br>
	@if ($link = $event->primary_link)
	<a href="{{ $link }}" target="_" title="Primary link">
		<i class="bi bi-link-45deg"></i>
	</a>
	@endif
	@if ($ticket = $event->ticket_link)
	<a href="{{ $link }}" target="_" title="Ticket link">
		<i class="bi bi-cart-fill"></i>
	</a>
	@endif

	{{ $event->attendingCount }} users attending

 	<br><br>

	<p>
	@if ($event->description)
	<event class="body">
		{!! nl2br($event->description) !!}
	</event>
	@endif

	<br>
	<i>Added by <a href="{{ $url }}/users/{{ $event->user->id }}">{{ $event->user->name ?? '' }}</a></i>

	<P>
	@unless ($event->entities->isEmpty())
	Related Entities:
		@foreach ($event->entities as $entity)
		<span class="label label-tag"><a href="{{ $url }}/events/relatedto/{{ $entity->slug }}">{{ $entity->name }}</a></span>
		@endforeach
	@endunless
	</P>

	@unless ($event->tags->isEmpty())
	<P>Tags:
	@foreach ($event->tags as $tag)
		<span class="label label-tag"><a href="{{ $url }}/events/tag/{{ $tag->name }}">{{ $tag->name }}</a></span>
		@endforeach
	@endunless
	</P>

	</div>
</div>

@endforeach
@endif

@if (count($interests) > 0)
		<h3>Here's some updates on who you are following:</h3>
		@foreach ($interests as $entity => $list)
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
						<br>{{ $event->venue->name ?? 'No venue specified' }}
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
						<br>{{ $event->primary_link ?? ''}}
					@endif
					<br>
				</div>
				<br>
			@endforeach
		@endforeach
		<br><br>
	@endif

	We're constantly adding new features, functionality and updates to improve your experience. <br>
	If you have any feedback, don't hesitate to drop us a line.


	<P></P>
	Thanks!<br>
	{{ $site }}<br>
	{{ $url }}
	</body>
	</html>
