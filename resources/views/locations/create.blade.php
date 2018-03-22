@extends('app')

@section('title','Event Add Location')

@section('content')

	<P><B>Entity</B> > {!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}</P>

	<h3>Add a New Location</h3>

	{!! Form::open(['route' => ['entities.locations.store', $entity->slug], 'method' => 'POST']) !!}

		@include('locations.form')

	{!! Form::close() !!}

@stop
