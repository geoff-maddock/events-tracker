@extends('app')

@section('title','Event Series -  Create Occurrence')

@section('content')

	<h1>{{ $series->name}} </h1> 
	<h2>Add Occurrence: {{ $event->name }}</h2>

	{!! Form::model($event, ['route' => ['events.create', $event->id], 'method' => 'PATCH']) !!}

		@include('events.form', ['action' => 'createOccurence'])

	{!! Form::close() !!}

	<P><a href="{!! URL::route('series.index') !!}" class="btn btn-info">Return to list</a></P>
@stop
