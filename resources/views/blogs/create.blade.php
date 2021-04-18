@extends('app')

@section('content')

	<h4>Add a New Blog</h4>

	{!! Form::open(['route' => 'blogs.store']) !!}

		@include('blogs.form')

	{!! Form::close() !!}

	{!! link_to_route('blogs.index', 'Return to list') !!}
@stop
