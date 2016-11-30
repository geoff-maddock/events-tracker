@extends('app')

@section('title','Entity Comment Edit')

@section('content')

	<P><B>Entity</B> > {!! link_to_route('entities.show', $entity->name, [$entity->id], ['class' => 'text-'.$entity->entityStatus->getDisplayClass()]) !!}</P>

	<h1>Edit Comment: <i>{{ $comment->name }}</i> </h1> 

	{!! Form::model($comment, ['route' => ['entities.comments.update', $entity->id, $comment->id], 'method' => 'PATCH']) !!}

		@include('comments.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form(['entities.comments.destroy', $entity->id,  $comment->id]) !!}</P>


@stop
