@extends('app')

@section('content')

	<h3>Add a New Group</h3>

	{!! Form::open(['route' => 'groups.store']) !!}

		@include('groups.form')

	{!! Form::close() !!}

	{!! link_to_route('groups.index', 'Return to list') !!}
@stop
