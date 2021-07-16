@extends('app')

@section('content')

	<h3>Add a New User</h3>

	{!! Form::open(['route' => 'users.store']) !!}

		@include('users.form')

	{!! Form::close() !!}

	{!! link_to_route('users.index', 'Return to list') !!}
@stop
