You might be interested in this new event because you are following <b>{!! $object->name !!}</b>.

<div class='event-date'>
	<h2>{!! $event->start_at->format('l F jS Y') !!}</h2>

	{!! $event->start_at->format('g:i A') !!} {!! $event->end_time ? 'until '.$event->end_time->format('g:i A') : '' !!}
	</div>

	<h2>{{ $event->name }}</h2>
	<i>{{ $event->short }}</i><br>

	<b>
	@if (!empty($event->series_id))
	<a href="{{ url('series/'.$event->series_id) }}">{!! $event->series->name !!}</a> series
	@endif

	<a href="{{ url('events/type/'.$event->eventType->name) }}">{{ $event->eventType->name }}</a>
	<br>

	@if (!empty($event->venue_id))
	<a href="{{ url('entities/'.$event->venue->slug) }}">{!! $event->venue->name !!}</a>

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
		<i class="bi bi-3"></i>
	</a>
	@endif

 	<br><br>

	<p>
	@if ($event->description)
	<event class="body">
		{!! nl2br($event->description) !!}
	</event>
	@endif

	<br>
	<i>Added by <a href="{{ url('users/'.$event->user->id) }}">{{ $event->user->name ?? '' }}</a></i>

	<P>
	@unless ($event->entities->isEmpty())
	Related Entities:
		@foreach ($event->entities as $entity)
		<span class="label label-tag"><a href="{{ url('events/related-to/'.$entity->slug) }}">{{ $entity->name }}</a></span>
		@endforeach
	@endunless
	</P>

	@unless ($event->tags->isEmpty())
	<P>Tags:
	@foreach ($event->tags as $tag)
		<span class="label label-tag"><a href="{{ url('events/tag/'.$tag->name) }}">{{ $tag->name }}</a></span>
		@endforeach
	@endunless
	</P>
	</div>
	</div>
