@extends('layouts.app-tw')

@section('title', 'Role View')

@section('content')

<div class="container mx-auto max-w-4xl">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary">{{ $role->name }}</h1>
		<p class="text-muted-foreground mt-2">Role Details</p>
	</div>

	<!-- Action Menu -->
	<div class="mb-6 flex gap-2">
		<a href="{{ route('roles.edit', $role->id) }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
			<i class="bi bi-pencil mr-2"></i>
			Edit Role
		</a>
		<a href="{{ route('roles.index') }}" class="inline-flex items-center px-4 py-2 bg-muted text-foreground rounded-lg hover:bg-muted/80 transition-colors">
			<i class="bi bi-arrow-left mr-2"></i>
			Return to List
		</a>
	</div>

	<!-- Role Details Card -->
	<div class="card-tw">
		<div class="p-6">
			<h2 class="text-2xl font-bold text-foreground mb-4">{{ $role->name }}</h2>

			<div class="space-y-3">
				@if ($role->name)
				<div>
					<label class="font-semibold text-foreground">Name: </label>
					<span class="text-muted-foreground">{{ $role->name }}</span>
				</div>
				@endif

				@if ($role->slug)
				<div>
					<label class="font-semibold text-foreground">Slug: </label>
					<span class="text-muted-foreground">{{ $role->slug }}</span>
				</div>
				@endif

				@if ($role->short)
				<div>
					<label class="font-semibold text-foreground">Short: </label>
					<span class="text-muted-foreground">{{ $role->short }}</span>
				</div>
				@endif
			</div>

			<!-- Delete Form -->
			<div class="mt-6">
				<form action="{{ route('roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role?');">
					@csrf
					@method('DELETE')
					<button type="submit" class="inline-flex items-center px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:bg-destructive/90 transition-colors">
						<i class="bi bi-trash mr-2"></i>
						Delete Role
					</button>
				</form>
			</div>
		</div>
	</div>
</div>

@stop
