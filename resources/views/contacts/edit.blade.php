@extends('app')

@section('title','Entity Contact Edit')

@section('content')

	<P><B>Entity</B> > {!! link_to_route('entities.show', $entity->name, [$entity->id], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}</P>

	<h1>Edit Contact: <i>{{ $contact->name }}</i> </h1> 

	{!! Form::model($contact, ['route' => ['entities.contacts.update', $entity->id, $contact->id], 'method' => 'PATCH']) !!}

		@include('contacts.form', ['action' => 'update'])

	{!! Form::close() !!}

	<div class="col-md-3">
	<P>{!! delete_form(['entities.contacts.destroy', $entity->id,  $contact->id]) !!}</P>
	</div>

@stop
