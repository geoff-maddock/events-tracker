@extends('app')

@section('content')

@section('title','User Profile View')


	<h2>{{ $user->name }}</h2>
	<p>
	@if ($user && (Auth::user()->id == $user->id || $user->id == Config::get('app.superuser') ) )	
		<a href="{!! route('users.edit', ['id' => $user->id]) !!}" class="btn btn-primary">Edit Profile</a>
	@endif
		<a href="{!! URL::route('users.index') !!}" class="btn btn-info">Return to list</a>

	</p>
	<b>Name </b> {{ $user->profile->first_name }} {{ $user->profile->last_name }}<br>
	<b>Alias </b> {{ $user->profile->alias }}<br>
	<b>Contact </b> <a href="mailto:{{ $user->email }}">{{ $user->email }}</a><br>

	<div class="bio">
	<b>Bio</b><br>
	<p>
		{{ $user->profile->bio or 'No bio available'}}
	</p>
	</div>
	<h5>Added <b>{{ $user->created_at->format('l F jS Y') }}</b></h5>
	<br>

	<div class="row">
	<div class="col-md-12">

	<h3>Events Attending {{ $user->attendingCount }}</h3>

	<div class="col-lg-6">
		<div class="bs-component">
			<div class="panel panel-info">


				<div class="panel-heading">
					<h3 class="panel-title">Past Events</h3>
				</div>

				<div class="panel-body">
				@include('events.list', ['events' => $user->events->take(20)])

				</div>

			</div>
		</div>
	</div>



@stop
