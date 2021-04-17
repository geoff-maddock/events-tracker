@extends('app')

@section('content')

	<h4>Add a New Menu</h4>

	{!! Form::open(['route' => 'menus.store']) !!}

		@include('menus.form')

	{!! Form::close() !!}

	{!! link_to_route('menus.index', 'Return to list') !!}
@stop
