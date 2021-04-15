@extends('app')

@section('content')

	<h4>Add a New Permission</h4>

	{!! Form::open(['route' => 'permissions.store']) !!}

		@include('permissions.form')

	{!! Form::close() !!}

	{!! link_to_route('permissions.index', 'Return to list') !!}
@stop
