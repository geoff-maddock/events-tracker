@extends('app')

@section('title','Event Add Contact')

@section('content')

<h1 class="display-6 text-primary"><B>Entity</B> . {!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}</h1>

	<h4>Add a New Contact</h4>

	{!! Form::open(['route' => ['entities.contacts.store', $entity->slug], 'method' => 'POST']) !!}

		@include('contacts.form')

	{!! Form::close() !!}

@stop
