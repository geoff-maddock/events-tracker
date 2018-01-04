@extends('app')

@section('title','Event Add Review')

@section('content')

	<P><B>Event</B> > {!! link_to_route('events.show', $event->id, [$event->id]) !!}</P>

	<h3>Add a Review</h3>

	{!! Form::open(['route' => ['events.reviews.store', $event->id], 'method' => 'POST']) !!}

		@include('reviews.form')

	{!! Form::close() !!}

@stop
