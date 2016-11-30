@extends('app')

@section('title','Event Edit')

@section('content')

<h1>Event . EDIT 
	@include('events.crumbs', ['slug' => $event->slug ? $event->slug : $event->id])
</h1>

	{!! Form::model($event, ['route' => ['events.update', $event->id], 'method' => 'PATCH']) !!}

		@include('events.form', ['action' => 'update'])

	{!! Form::close() !!}

	<P>{!! delete_form(['events.destroy', $event->id]) !!}</P>

	<P><a href="{!! URL::route('events.index') !!}" class="btn btn-info">Return to list</a></P>
@stop
