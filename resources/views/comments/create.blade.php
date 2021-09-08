@extends('app')

@section('title','Add Comment')

@section('content')

	<h1 class="display-6 text-primary">{{ ucfirst($type) }}</B> . {!! link_to_route(Str::plural($type).'.show', $object->name, [$object->slug]) !!}</h1>

	<h4>Add a New Comment</h4>

	{!! Form::open(['route' => [Str::plural($type).'.comments.store', $object->getRouteKey()], 'method' => 'POST']) !!}

		@include('comments.form')

	{!! Form::close() !!}

@stop
