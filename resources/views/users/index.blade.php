@extends('app')

@section('title','Events')

@section('content')

		<h1>Users</h1>
		<i>public user directory</i>


			<ul class='user-list'>
				@foreach ($users as $user)
				<li>
					<b><a href="{!! route('users.show', ['id' => $user->id]) !!}" title="Added {!! $user->created_at->format('l F jS Y') !!}">{{ $user->name }} </b>
					<span>
					@if ($user && (Auth::user()->id == $user->id || $user->id == Config::get('app.superuser') ) )	
					<a href="{!! route('users.edit', ['id' => $user->id]) !!}"><span class='glyphicon glyphicon-pencil'></span></a>
					@endif
					</span>
				</li>

				@endforeach
			</ul>

@stop
