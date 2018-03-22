@extends('app')

@section('title','Event Add Contact')

@section('content')

	<P><B>Entity</B> > {!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}</P>

	<h3>Add a New Contact</h3>

	{!! Form::open(['route' => ['entities.contacts.store', $entity->slug], 'method' => 'POST']) !!}

		@include('contacts.form')

	{!! Form::close() !!}

@stop
