@extends('app')

@section('title', 'Thread Add')

@section('content')

	<h4>Add a New Thread</h4>

	{!! Form::open(['route' => 'threads.store']) !!}

		@include('threads.form')

	{!! Form::close() !!}

	{!! link_to_route('threads.index', 'Return to list') !!}
@stop
