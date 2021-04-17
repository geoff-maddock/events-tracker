@extends('app')

@section('title','Event Add Review')

@section('content')

	<h4><B>Event</B>  {!! link_to_route('events.show', $event->name, [$event->id]) !!}</P>

		<a href="{!! route('events.show', ['event' => $event->id]) !!}" class="btn btn-primary">View Event</a>
	<h4>Add a Review</h4>

	{!! Form::open(['route' => ['events.reviews.store', $event->id], 'method' => 'POST']) !!}

		@include('reviews.form')

	{!! Form::close() !!}

@stop
