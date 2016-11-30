@extends('app')

@section('title','Events')

@section('content')

		<h1>Users</h1>
		<i>public user directory</i>


			<ul class='user-list'>
				@foreach ($users as $user)
				<li>
					<h3>{{ $user->name }}</h3> <a href="{!! route('users.show', ['id' => $user->id]) !!}"><span class='glyphicon glyphicon-search'></span></a>
					<span class='user-created'>Added {!! $user->created_at->format('l F jS Y') !!}</span>
				</li>

				@endforeach
			</ul>

@stop
