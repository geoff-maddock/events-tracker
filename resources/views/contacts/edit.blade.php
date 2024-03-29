@extends('app')

@section('title','Entity Contact Edit')

@section('content')

	<h1 class="display-6 text-primary">Entity . {!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}</h1>

	<h4>Edit Contact: <i>{{ $contact->name }}</i> </h4> 

	{!! Form::model($contact, ['route' => ['entities.contacts.update', $entity->slug, $contact->id], 'method' => 'PATCH']) !!}

		@include('contacts.form', ['action' => 'update'])

	{!! Form::close() !!}

	<div class="col-md-3 mx-2">
	{!! delete_form(['entities.contacts.destroy', $entity->slug,  $contact->id]) !!}
	</div>

@stop
