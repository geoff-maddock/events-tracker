@extends('app')

@section('title','Event Review Edit')

@section('content')

<h1 class="display-6 text-primary">Review . {!! link_to_route('events.show', $review->event->name, ['event' => $review->event->id]) !!}</h1>

<div id="action-menu" class="mb-2">
	<a href="{!! route('reviews.index') !!}" class="btn btn-primary">Reviews List</a>
	<a href="{!! route('events.show', ['event' => $review->event->id]) !!}" class="btn btn-primary">View Event</a>
</div>

	{!! Form::model($review, ['route' => ['events.reviews.update', $review->event->id, $review->id], 'method' => 'PATCH']) !!}

		@include('reviews.form', ['action' => 'update'])

	{!! Form::close() !!}

	<div class="col-md-3">
	<P>{!! delete_form(['events.reviews.destroy', $review->event->id,  $review->id]) !!}</P>
	</div>

@stop
