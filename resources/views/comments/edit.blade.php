@extends('app')

@section('title','Entity Comment Edit')

@section('content')

	<P><B>Comment on</B> > {!! link_to_route('entities.show', $object->name, [$object->id], ['class' => 'text-'.((get_class($object) == 'entity') ? $object->entityStatus->getDisplayClass() : '')]) !!}</P>

	<h4>Edit Comment: <i>{{ $comment->name }}</i> </h4> 

	{!! Form::model($comment, ['route' => [Str::plural($type).'.comments.update', $object->slug, $comment->id], 'method' => 'PATCH']) !!}

		@include('comments.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form([Str::plural($type).'.comments.destroy', $object->id,  $comment->id]) !!}</P>


@stop
