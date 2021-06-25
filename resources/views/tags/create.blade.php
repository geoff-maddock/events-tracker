@extends('app')

@section('title', 'Tag Add')

@section('content')

	<h4>Add a New Tag</h4>

	{!! Form::open(['route' => 'tags.store']) !!}

		@include('tags.form')

	{!! Form::close() !!}

	{!! link_to_route('tags.index', 'Return to list') !!}
@stop
