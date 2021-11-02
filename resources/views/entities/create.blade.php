@extends('app')

@section('title', 'Entity Add')

@section('content')

	<h1 class="display-6 text-primary">Add a New Entity</h1>

	{!! Form::open(['route' => 'entities.store']) !!}

		@include('entities.form')

	{!! Form::close() !!}

	{!! link_to_route('entities.index', 'Return to list') !!}
@stop
