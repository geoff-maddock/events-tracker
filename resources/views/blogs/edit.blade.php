@extends('app')

@section('title','Blog Edit')

@section('content')

<h1 class="display-6 text-primary">Blog . Edit . {{ $blog->name }}</h1>

	{!! Form::model($blog, ['route' => ['blogs.update', $blog->id], 'method' => 'PATCH']) !!}

		@include('blogs.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['blogs.destroy', $blog->id]) !!}

	{!! link_to_route('blogs.index','Return to list') !!}
@stop
