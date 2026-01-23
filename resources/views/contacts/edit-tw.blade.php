@extends('layouts.app-tw')

@section('title', 'Edit Contact')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Edit Contact</h1>
		<p class="text-muted-foreground">
			{{ $contact->name }} - <a href="{{ route('entities.show', $entity->slug) }}" class="text-primary hover:underline">{{ $entity->name }}</a>
		</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw mb-6">
		<div class="p-6">
			<form action="{{ route('entities.contacts.update', [$entity->slug, $contact->id]) }}" method="POST">
				@csrf
				@method('PATCH')

				@include('contacts.form-tw', ['action' => 'update'])
			</form>
		</div>
	</div>

	<!-- Delete Section -->
	<div class="card-tw border-destructive/20">
		<div class="p-6">
			<h2 class="text-lg font-semibold text-destructive mb-2">Danger Zone</h2>
			<p class="text-sm text-muted-foreground mb-4">Once you delete a contact, there is no going back. Please be certain.</p>

			<form action="{{ route('entities.contacts.destroy', [$entity->slug, $contact->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this contact? This action cannot be undone.');">
				@csrf
				@method('DELETE')
				<button type="submit" class="inline-flex items-center px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:bg-destructive/90 transition-colors">
					<i class="bi bi-trash mr-2"></i>
					Delete Contact
				</button>
			</form>
		</div>
	</div>
</div>

@stop
