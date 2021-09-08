@extends('app')

@section('content')

<h1 class="display-6 text-primary">Add a New Entity Type</h1>

	{!! Form::open(['route' => 'entity-types.store']) !!}

		@include('entityTypes.form')

	{!! Form::close() !!}

	{!! link_to_route('entity-types.index', 'Return to list') !!}
@stop
