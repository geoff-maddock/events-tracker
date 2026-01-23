@extends('layouts.app-tw')

@section('title', 'Add Link')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Add a New Link</h1>
		<p class="text-muted-foreground">Create a new link</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw">
		<div class="p-6">
			<form action="{{ route('entities.links.store', $entity->slug) }}" method="POST">
				@csrf

				@include('links.form-tw')
			</form>
		</div>
	</div>

	<!-- Back Link -->
	<div class="mt-4">
		<a href="{{ route('entities.show', $entity->slug) }}" class="text-primary hover:text-primary/90 flex items-center gap-2">
			<i class="bi bi-arrow-left"></i>
			<span>Return to list</span>
		</a>
	</div>
</div>

@stop
