@extends('app')

@section('title', 'Tag Edit')

@section('content')

	<h4>Tag . EDIT : {{ $tag->name }}</h4>
	<br> 	<a href="{!! route('tags.show', ['tag' => $tag->id]) !!}" class="btn btn-primary">Show Tag</a> <a href="{!! URL::route('tags.index') !!}" class="btn btn-info">Return to list</a>


	{!! Form::model($tag, ['route' => ['tags.update', $tag->id], 'method' => 'PATCH']) !!}

		@include('tags.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['tags.destroy', $tag->id]) !!}

@stop
