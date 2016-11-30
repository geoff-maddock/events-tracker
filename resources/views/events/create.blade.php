@extends('app')

@section('title', 'Event Add')

@section('content')

	<h1>Add a New Event</h1>

	{!! Form::open(['route' => 'events.store']) !!}

		@include('events.form')

	{!! Form::close() !!}

	{!! link_to_route('events.index', 'Return to list') !!}
@stop
