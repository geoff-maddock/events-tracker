@extends('app')

@section('content')


	<i>{{ $event->start_at->format('l F jS Y \\a\\t h:i A') }} </i> 
	<h2>Edit: {{ $event->name }}</h2>

	{!! Form::model($event, ['route' => ['events.update', $event->id], 'method' => 'PATCH']) !!}

		@include('events.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['events.destroy', $event->id]) !!}

	{!! link_to_route('events.index','Return to list') !!}
@stop
