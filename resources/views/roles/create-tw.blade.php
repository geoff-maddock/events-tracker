@extends('layouts.app-tw')

@section('title', 'Add Role')

@section('content')

<div class="container mx-auto max-w-2xl">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary">Add Role</h1>
		<p class="text-muted-foreground mt-2">Create a new role</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw">
		<div class="p-6">
			<form method="POST" action="{{ route('roles.store') }}">
				@csrf

				@include('roles.form-tw', ['action' => 'store', 'role' => new App\Models\Role])
			</form>
		</div>
	</div>
</div>

@stop
