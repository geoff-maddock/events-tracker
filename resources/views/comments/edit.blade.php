@extends('app')

@section('title','Entity Comment Edit')

@section('content')

	<P><B>Comment on</B> > {!! link_to_route('entities.show', $object->name, [$object->id], ['class' => 'text-'.((get_class($object) == 'entity') ? $object->entityStatus->getDisplayClass() : '')]) !!}</P>

	<h1>Edit Comment: <i>{{ $comment->name }}</i> </h1> 

	{!! Form::model($comment, ['route' => [str_plural($type).'.comments.update', $object->id, $comment->id], 'method' => 'PATCH']) !!}

		@include('comments.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form([str_plural($type).'.comments.destroy', $object->id,  $comment->id]) !!}</P>


@stop
