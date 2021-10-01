@extends('app')

@section('title','Permission Edit')

@section('content')


<h1 class="display-6 text-primary">Permission . Edit . {{ $permission->name }}</h1>

	{!! Form::model($permission, ['route' => ['permissions.update', $permission->id], 'method' => 'PATCH']) !!}

		@include('permissions.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['permissions.destroy', $permission->id]) !!}

	{!! link_to_route('permissions.index','Return to list') !!}
@stop
