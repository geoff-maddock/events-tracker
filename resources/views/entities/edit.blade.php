@extends('app')

@section('title','Entity Edit')

@section('content')


	<h2>Edit: {{ $entity->name }}</h2>

	{!! Form::model($entity, ['route' => ['entities.update', $entity->id], 'method' => 'PATCH']) !!}

		@include('entities.form', ['action' => 'update', 'entityTypes' => $entityTypes])

	{!! Form::close() !!}

	{!! delete_form(['entities.destroy', $entity->id]) !!}

	{!! link_to_route('entities.index','Return to list') !!}
@stop
