@extends('app')

@section('content')

<h1 class="display-crumbs text-primary">Add a New Menu</h1>

	{!! Form::open(['route' => 'menus.store']) !!}

		@include('menus.form')

	{!! Form::close() !!}

	{!! link_to_route('menus.index', 'Return to list') !!}
@stop
