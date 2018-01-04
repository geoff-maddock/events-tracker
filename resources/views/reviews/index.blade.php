@extends('app')
 
@section('content')
    <h2>
        {!! link_to_route('events.show', $event, [$event->id]) !!} -
        {{ $review->review }}
    </h2>
 
    {{ $review->rating }}
@endsection