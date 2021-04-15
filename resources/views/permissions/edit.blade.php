@extends('app')

@section('title','Permission Edit')

@section('content')


	<h4>Edit: {{ $permission->name }}</h4>

	{!! Form::model($permission, ['route' => ['permissions.update', $permission->id], 'method' => 'PATCH']) !!}

		@include('permissions.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['permissions.destroy', $permission->id]) !!}

	{!! link_to_route('permissions.index','Return to list') !!}
@stop
