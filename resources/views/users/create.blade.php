@extends('app')

@section('content')

	<h3>Add a New Event</h3>

	{!! Form::open(['route' => 'events.store']) !!}

		@include('events.form')

	{!! Form::close() !!}

	{!! link_to_route('events.index', 'Return to list') !!}
@stop
