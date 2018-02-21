@extends('app')

@section('title','Group Edit')

@section('content')


	<h2>Group . EDIT : {{ $group->name }}</h2>
	<br> 	<a href="{!! route('groups.show', ['id' => $group->id]) !!}" class="btn btn-primary">Show Group</a> <a href="{!! URL::route('groups.index') !!}" class="btn btn-info">Return to list</a>


	{!! Form::model($group, ['route' => ['groups.update', $group->id], 'method' => 'PATCH']) !!}

		@include('groups.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['groups.destroy', $group->id]) !!}

@stop
