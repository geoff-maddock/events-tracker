@extends('app')

@section('title','Entity Link Edit')

@section('content')

<h1 class="display-6 text-primary">Entity . {!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}</h1>

	<h4>Edit Link: <i>{{ $link->text }}</i> </h4> 

	<div class="row">
	{!! Form::model($link, ['route' => ['entities.links.update', $entity->slug, $link->id], 'method' => 'PATCH']) !!}

		@include('links.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['entities.links.destroy', $entity->slug,  $link->id]) !!}
	</div>

@stop
