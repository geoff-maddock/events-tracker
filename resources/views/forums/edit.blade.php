@extends('app')

@section('title','Forum Edit')

@section('content')

<h1>Forum . EDIT 
	@include('forums.crumbs', ['slug' => $forum->slug ? $forum->slug : $forum->id])
</h1>

	{!! Form::model($forum, ['route' => ['forums.update', $forum->id], 'method' => 'PATCH']) !!}

		@include('forums.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form(['forums.destroy', $forum->id]) !!}</P>

	<P><a href="{!! URL::route('forums.index') !!}" class="btn btn-info">Return to list</a></P>
@stop
