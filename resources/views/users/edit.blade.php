@extends('app')

@section('content')


	<h2>{{ $user->name }}</h2>
	<p>

		<a href="{!! route('users.show', ['id' => $user->id]) !!}" class="btn btn-primary">Show Profile</a>
		<a href="{!! URL::route('users.index') !!}" class="btn btn-info">Return to list</a>

	</p>

	{!! Form::model($user->profile, ['route' => ['users.update', $user->id], 'method' => 'PATCH']) !!}

		@include('users.form', ['action' => 'update'])

	{!! Form::close() !!}

	{!! delete_form(['users.destroy', $user->id]) !!}

	{!! link_to_route('users.index','Return to list') !!}
@stop
