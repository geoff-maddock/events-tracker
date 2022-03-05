@if (count($events) > 0)

<?php $month = ''; ?>
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
{{ $entity->name }}@if (!$loop->last), @endif
@endforeach
@endunless

@unless ($event->tags->isEmpty())
Tags:
@foreach ($event->tags as $tag)
{{ $tag->name }}@if (!$loop->last), @endif
@endforeach
@endunless

@if ($event->primary_link)
<br>{{ $event->primary_link ?? ''}}
@endif
<br><br>
@endforeach


@else
No events listed
@endif