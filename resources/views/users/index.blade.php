@extends('app')

@section('title','Events')

@section('content')

		<h1>Users</h1>
		<i>public user directory</i>


			<ul class='user-list'>
				@foreach ($users as $x)
				<li>
					<b><a href="{!! route('users.show', ['id' => $x->id]) !!}" title="Added {!! $x->created_at->format('l F jS Y') !!}">{{ $x->name }} </b>
					<span>
					@if ($signedIn && (Auth::user()->id == $user->id || $user->id == Config::get('app.superuser') ) )	
					<a href="{!! route('users.edit', ['id' => $user->id]) !!}"><span class='glyphicon glyphicon-pencil'></span></a>
					@endif
					</span>
				</li>

				@endforeach
			</ul>

@stop
