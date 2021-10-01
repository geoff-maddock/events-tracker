@extends('app')

@section('content')

<h1 class="display-6 text-primary">Add a New Permission</h1>

{!! Form::open(['route' => 'permissions.store']) !!}

	@include('permissions.form')

{!! Form::close() !!}

{!! link_to_route('permissions.index', 'Return to list') !!}
@stop
