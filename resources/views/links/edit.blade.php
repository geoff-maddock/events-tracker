@extends('app')

@section('title','Entity Link Edit')

@section('content')

	<h4><B>Entity</B> . {!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}</h4>

	<h4>Edit Link: <i>{{ $link->text }}</i> </h4> 

	{!! Form::model($link, ['route' => ['entities.links.update', $entity->slug, $link->id], 'method' => 'PATCH']) !!}

		@include('links.form', ['action' => 'update'])

	{!! Form::close() !!}

	<div class="col-md-3">
	<P>{!! delete_form(['entities.links.destroy', $entity->slug,  $link->id]) !!}</P>
	</div>

@stop
