@extends('layout')

@section('content')

	<h2>{{ $article->title }}</h2>

	{!! Form::model($article, ['route' => ['articles.update', $article->id], 'method' => 'PATCH']) !!}

		@include('articles.form')

	{!! Form::close() !!}

	{!! delete_form(['articles.destroy', $article->id]) !!}

	{!! link_to_route('articles.index','Return to list') !!}
@stop
