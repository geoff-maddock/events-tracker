@extends('app')

@section('title', 'Thread Add')

@section('content')

<h1 class="display-crumbs text-primary">Add a New Thread</h1>

	{!! Form::open(['route' => 'threads.store']) !!}

		@include('threads.form')

	{!! Form::close() !!}

	{!! link_to_route('threads.index', 'Return to list') !!}
@stop
