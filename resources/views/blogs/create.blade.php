@extends('app')

@section('content')

<h1 class="display-6 text-primary">Add a New Blog</h1>

	{!! Form::open(['route' => 'blogs.store']) !!}

		@include('blogs.form')

	{!! Form::close() !!}

	{!! link_to_route('blogs.index', 'Return to list') !!}
@stop
