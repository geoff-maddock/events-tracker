@extends('app')

@section('content')

	<h3>Add a New Blog</h3>

	{!! Form::open(['route' => 'blogs.store']) !!}

		@include('blogs.form')

	{!! Form::close() !!}

	{!! link_to_route('blogs.index', 'Return to list') !!}
@stop
