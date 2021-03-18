@extends('app')

@section('content')

	<h4>Add a New Entity</h4>

	{!! Form::open(['route' => 'entities.store']) !!}

		@include('entities.form')

	{!! Form::close() !!}

	{!! link_to_route('entities.index', 'Return to list') !!}
@stop
