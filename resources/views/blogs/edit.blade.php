@extends('app')

@section('title','Blog Edit')

@section('content')

<h1 class="display-6 text-primary">Blog . Edit . {{ $blog->name }}</h1>

	{!! Form::model($blog, ['route' => ['blogs.update', $blog->slug], 'method' => 'PATCH']) !!}

		@include('blogs.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['blogs.destroy', $blog->slug]) !!}

	{!! link_to_route('blogs.index','Return to list') !!}
@stop
