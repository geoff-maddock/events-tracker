@extends('app')

@section('title','Menu Edit')

@section('content')


	<h2>Edit: {{ $menu->name }}</h2>

	{!! Form::model($menu, ['route' => ['menus.update', $menu->id], 'method' => 'PATCH']) !!}

		@include('menus.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['menus.destroy', $menu->id]) !!}

	{!! link_to_route('menus.index','Return to list') !!}
@stop
