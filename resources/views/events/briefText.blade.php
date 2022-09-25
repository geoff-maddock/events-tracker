@extends('minimal')


@section('title', 'Event Feed')

@section('content')
@if (count($events) > 0)

<?php $month = ''; ?>
@foreach ($events as $event)

@if ($month != $event->start_at->format('F'))
<?php $month = $event->start_at->format('F')?>
@endif

{!! $event->start_at->format('m/d') !!} {{ $event->name }}


@if ($event->venue)
{{ $event->venue->name  }}
@endif


@if ($event->door_price)
${{ number_format($event->door_price,0) }}
@endif


@unless ($event->tags->isEmpty())
@php 
$start = 0;
$limit = 4;
@endphp
@foreach ($event->tags as $tag)
#{{ strtolower($tag->name) }}@if (!$loop->last) @endif
@php
    if ($start >= $limit) {
        break;
    }
    $start++;
@endphp
@endforeach
@endunless
<br>
@endforeach
@endif
@stop
