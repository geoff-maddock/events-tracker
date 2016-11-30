@extends('layout')

@section('content')

	<h2>Add a New Article</h2>

	{!! Form::open(['route' => 'articles.store']) !!}

		@include('articles.form')

	{!! Form::close() !!}

	{!! link_to_route('articles.index','Return to list') !!}
@stop
