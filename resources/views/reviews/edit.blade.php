@extends('app')

@section('title','Event Review Edit')

@section('content')

	<h4><B>Event</B>  {!! link_to_route('events.show', $review->event->name, ['event' => $review->event->id]) !!}</P>

	<a href="{!! route('events.show', ['event' => $review->event->id]) !!}" class="btn btn-primary">View Event</a>

	<h1>Edit Review by <i>{{ $review->user->name }}</i> </h1> 

	{!! Form::model($review, ['route' => ['events.reviews.update', $review->event->id, $review->id], 'method' => 'PATCH']) !!}

		@include('reviews.form', ['action' => 'update'])

	{!! Form::close() !!}

	<div class="col-md-3">
	<P>{!! delete_form(['events.reviews.destroy', $review->event->id,  $review->id]) !!}</P>
	</div>

@stop
