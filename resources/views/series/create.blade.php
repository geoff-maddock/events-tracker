@extends('app')

@section('content')

	<h3>Add a New Event Series</h3>

	{!! Form::open(['route' => 'series.store']) !!}

		@include('series.form')

	{!! Form::close() !!}

	{!! link_to_route('series.index', 'Return to list') !!}
@stop
