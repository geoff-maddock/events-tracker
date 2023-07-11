@extends('app')

@section('title', 'Keyword Tag Edit')

@section('content')

<h1 class="display-crumbs text-primary">Keyword Tag . Edit . {{ $tag->name }}</h1>
<div id="action-menu" class="mb-2">
	<a href="{!! route('tags.show', ['tag' => $tag->slug]) !!}" class="btn btn-primary">Show Tag</a>
	<a href="{!! URL::route('tags.index') !!}" class="btn btn-info">Return to list</a>
</div>


	{!! Form::model($tag, ['route' => ['tags.update', $tag->id], 'method' => 'PATCH']) !!}

		@include('tags.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['tags.destroy', $tag->id]) !!}

@stop
