@extends('app')

@section('title', 'Forum Add')

@section('content')

	<h1>Add a New Forum</h1>

	{!! Form::open(['route' => 'forums.store']) !!}

		@include('forums.form')

	{!! Form::close() !!}

	{!! link_to_route('forums.index', 'Return to list') !!}
@stop
