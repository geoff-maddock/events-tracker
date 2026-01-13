@extends('layouts.app-tw')

@section('title', 'Add Contact')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">
			Entity - <a href="{{ route('entities.show', $entity->slug) }}" class="hover:underline">{{ $entity->name }}</a>
		</h1>
		<p class="text-muted-foreground">Add a new contact</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw">
		<div class="p-6">
			<form action="{{ route('entities.contacts.store', $entity->slug) }}" method="POST">
				@csrf

				@include('contacts.form-tw')
			</form>
		</div>
	</div>
</div>

@stop
