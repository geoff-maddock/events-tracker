@extends('layouts.app-tw')

@section('title', 'Add Location')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Add a New Location</h1>
		<p class="text-muted-foreground">Create a new location</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw">
		<div class="p-6">
			<form action="{{ route('entities.locations.store', $entity->slug) }}" method="POST">
				@csrf

				@include('locations.form-tw')
			</form>
		</div>
	</div>
</div>

@stop

@section('scripts.footer')
<script>
document.addEventListener('DOMContentLoaded', function() {
	const nameInput = document.getElementById('name');
	const slugInput = document.getElementById('slug');
	let manuallyEdited = false;

	// Function to generate slug from name
	function generateSlug(text) {
		return text
			.toLowerCase()
			.trim()
			.replace(/\s+/g, '-')
			.replace(/[^\w\-]+/g, '')
			.replace(/\-\-+/g, '-')
			.replace(/^-+/, '')
			.replace(/-+$/, '');
	}

	// Auto-generate slug when name changes
	if (nameInput && slugInput) {
		nameInput.addEventListener('input', function() {
			if (!manuallyEdited && !slugInput.value) {
				slugInput.value = generateSlug(this.value);
			}
		});

		slugInput.addEventListener('input', function() {
			manuallyEdited = true;
		});

		slugInput.addEventListener('blur', function() {
			if (!this.value) {
				manuallyEdited = false;
			}
		});
	}
});
</script>
@stop
