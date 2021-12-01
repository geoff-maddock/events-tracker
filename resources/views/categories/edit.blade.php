@extends('app')

@section('title','Category Edit')

@section('content')

<h1 class="display-6 text-primary">Category . Edit . {{ $category->name }}</h1>

	{!! Form::model($category, ['route' => ['categories.update', $category->id], 'method' => 'PATCH']) !!}

		@include('categories.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['categories.destroy', $category->id]) !!}

	{!! link_to_route('categories.index','Return to list') !!}
@stop
