@extends('app')

@section('title','Events')

@section('content')

		<h1>Users</h1>
		<i>public user directory</i><br>

		<div class="row">
		@include('users.list', ['users' => $users])
		</div>
@stop
