@extends('app')

@section('title','Event Add Links')

@section('content')

	<P><B>Entity</B> > {!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}</P>

	<h3>Add a New Link</h3>

	{!! Form::open(['route' => ['entities.links.store', $entity->slug], 'method' => 'POST']) !!}

		@include('links.form')

	{!! Form::close() !!}

@stop
