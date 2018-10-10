<html>
<body>
<div>
	You're invited to join {{ $site }}, a site that keeps you on top of local events, artists, music and other happenings.
</div>
<div>
	On <b>{{ $url }} you can follow events, artists, venues, promoters or tags to receive daily and weekly updates on related events,<br>
		add your own events for others to discover, add the site to your RSS feed, or just visit the site at your leisure without<br>
		registering or sharing any of your information.  It's up to you!
</div>
<P></P>

<P>
	<h3>Here's a sampling of some of the upcoming events listed on the site...</h3>
    <?php $month = '';?>
	@foreach ($events as $event)

		@if ($month != $event->start_at->format('F'))
            <?php $month = $event->start_at->format('F')?>
		@endif

		{!! $event->start_at->format('l F jS Y') !!} <br>
		{{ $event->name }}


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
		<br><br>
	@endforeach
</P>
<div>
	Visit us at <b><a href="{{ $url }}">{{ $url }}</a></b> or register by going to <b><a href="{{ $url }}/register">{{ $url }}/register</a></b><br>
</div>

<P></P>
Sincerly,<br>
{{ $site }}
{{ $url }}
</body>
</html>