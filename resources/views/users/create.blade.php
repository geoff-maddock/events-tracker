@extends('app')

@section('content')

<h1 class="display-6 text-primary">Add a New User</h1>

{!! Form::open(['route' => 'users.store']) !!}

	@include('users.form')

{!! Form::close() !!}

{!! link_to_route('users.index', 'Return to list') !!}
@stop
