@extends('app')

@section('title','Event Add Links')

@section('content')

	<h1 class="display-6 text-primary"><B>Entity</B> . {!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}</h1>

	<h4>Add a New Link</h4>

	{!! Form::open(['route' => ['entities.links.store', $entity->slug], 'method' => 'POST']) !!}

		@include('links.form')

	{!! Form::close() !!}

@stop
