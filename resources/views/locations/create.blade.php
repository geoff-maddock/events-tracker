@extends('app')

@section('title','Event Add Location')

@section('content')

	<h1 class="display-6 text-primary">{!! link_to_route('entities.index', 'Entity', [$entity->slug]) !!} . {!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}</h1>

	<h4>Add a New Location</h4>

	{!! Form::open(['route' => ['entities.locations.store', $entity->slug], 'method' => 'POST']) !!}

		@include('locations.form')

	{!! Form::close() !!}

@stop
