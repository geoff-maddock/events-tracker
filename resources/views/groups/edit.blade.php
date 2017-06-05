@extends('app')

@section('title','Group Edit')

@section('content')


	<h2>Edit: {{ $group->name }}</h2>

	{!! Form::model($group, ['route' => ['groups.update', $group->id], 'method' => 'PATCH']) !!}

		@include('groups.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['groups.destroy', $group->id]) !!}

	{!! link_to_route('groups.index','Return to list') !!}
@stop
