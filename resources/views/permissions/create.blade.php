@extends('app')

@section('content')

	<h3>Add a New Permission</h3>

	{!! Form::open(['route' => 'permissions.store']) !!}

		@include('permissions.form')

	{!! Form::close() !!}

	{!! link_to_route('permissions.index', 'Return to list') !!}
@stop
