@extends('app')

@section('title','Blog Edit')

@section('content')


	<h2>Edit: {{ $blog->name }}</h2>

	{!! Form::model($blog, ['route' => ['blogs.update', $blog->id], 'method' => 'PATCH']) !!}

		@include('blogs.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['blogs.destroy', $blog->id]) !!}

	{!! link_to_route('blogs.index','Return to list') !!}
@stop
