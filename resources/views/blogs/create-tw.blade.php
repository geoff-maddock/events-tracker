@extends('layouts.app-tw')

@section('title', 'Add Blog')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Add a New Blog</h1>
		<p class="text-muted-foreground">Create a new blog post</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw">
		<div class="p-6">
			<form action="{{ route('blogs.store') }}" method="POST">
				@csrf

				@include('blogs.form-tw')
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

	// Initialize Select2 for tags and entities
	$('#tag_list').select2({
		placeholder: 'Choose a tag',
		tags: true,
		theme: 'tailwind'
	});

	$('#entity_list').select2({
		placeholder: 'Choose a related artist, producer, dj',
		tags: false,
		theme: 'tailwind'
	});
});
</script>
@stop
