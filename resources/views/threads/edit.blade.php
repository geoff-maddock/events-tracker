@extends('app')

@section('title','Thread Edit')

@section('content')

<h4>Thread . EDIT 
	@include('threads.crumbs', ['slug' => $thread->slug ? $thread->slug : $thread->id])
</h4>

	{!! Form::model($thread, ['route' => ['threads.update', $thread->id], 'method' => 'PATCH']) !!}

		@include('threads.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form(['threads.destroy', $thread->id]) !!}</P>

	<P><a href="{!! URL::route('threads.index') !!}" class="btn btn-info">Return to list</a></P>
@stop
