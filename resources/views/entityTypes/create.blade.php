@extends('app')

@section('content')

	<h3>Add a New Entity Type</h3>

	{!! Form::open(['route' => 'entity-types.store']) !!}

		@include('entityTypes.form')

	{!! Form::close() !!}

	{!! link_to_route('entity-types.index', 'Return to list') !!}
@stop
