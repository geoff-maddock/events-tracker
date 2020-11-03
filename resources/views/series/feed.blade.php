@if (count($series) > 0)

	<?php $month = '';?>
	@foreach ($series as $s)

		@if ($month != $s->start_at->format('F'))
		<?php $month = $s->start_at->format('F')?>
		@endif

		{!! $s->start_at->format('l F jS Y') !!} <br>
		{{ $s->name }}


		@if (!empty($s->series_id))
		<br><a href="/series/{{$s->series_id }}">{!! $s->series->name !!}</a> series
		@endif
		<br>
		{{ $s->eventType->name }}

		@if ($s->venue)
		<br>{{ $s->venue->name ?? 'No venue specified' }}
			@if ($s->venue->getPrimaryLocationAddress() )
				{{ $s->venue->getPrimaryLocationAddress() }}
			@endif
		@else
		no venue specified
		@endif

		@if ($s->start_at)
		at {{ $s->start_at->format('g:i A') }}
		@endif

		@if ($s->door_price)
		${{ number_format($s->door_price,0) }}
		@endif


		@unless ($s->entities->isEmpty())
		<br>
		Related:
			@foreach ($s->entities as $entity)
				{{ $entity->name }},
			@endforeach
		@endunless

		@unless ($s->tags->isEmpty())
		Tags:
			@foreach ($s->tags as $tag)
				{{ $tag->name }},
			@endforeach
		@endunless

		@if ($s->primary_link)
		<br>{{ $s->primary_link ?? ''}}
		@endif
		<br><br>
	@endforeach


@else
No future series listed
@endif
