@extends('app')

@section('content')

	<h4>Add a New Group</h4>

	{!! Form::open(['route' => 'groups.store']) !!}

		@include('groups.form')

	{!! Form::close() !!}

	{!! link_to_route('groups.index', 'Return to list') !!}
@stop
