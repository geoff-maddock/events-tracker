@extends('app')

@section('title','Add Comment')

@section('content')

	<P><B>{{ ucfirst($type) }}</B> > {!! link_to_route(Str::plural($type).'.show', $object->name, [$object->id]) !!}</P>

	<h4>Add a New Comment</h4>

	{!! Form::open(['route' => [Str::plural($type).'.comments.store', $object->getRouteKey()], 'method' => 'POST']) !!}

		@include('comments.form')

	{!! Form::close() !!}

@stop
