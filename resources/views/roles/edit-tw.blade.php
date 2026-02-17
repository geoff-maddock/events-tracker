@extends('layouts.app-tw')

@section('title', 'Edit Role')

@section('content')

<div class="container mx-auto max-w-2xl">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary">Edit Role</h1>
		<p class="text-muted-foreground mt-2">Update role: {{ $role->name }}</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw">
		<div class="p-6">
			<form method="POST" action="{{ route('roles.update', $role) }}">
				@csrf
				@method('PUT')

				@include('roles.form-tw', ['action' => 'update', 'role' => $role])
			</form>
		</div>
	</div>
</div>

@stop
