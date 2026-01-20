@extends('layouts.app-tw')

@section('title', 'Add Keyword Tag')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Add a New Keyword Tag</h1>
		<p class="text-muted-foreground">Create a new tag to help categorize events</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw">
		<div class="p-6">
			<form action="{{ route('tags.store') }}" method="POST">
				@csrf

				@include('tags.form-tw')
			</form>
		</div>
	</div>

	<!-- Back Link -->
	<div class="mt-4">
		<a href="{{ route('tags.index') }}" class="text-primary hover:text-primary/90 flex items-center gap-2">
			<i class="bi bi-arrow-left"></i>
			<span>Return to list</span>
		</a>
	</div>
</div>

@stop

@section('scripts.footer')
<script>
document.addEventListener('DOMContentLoaded', function() {
	// Initialize Select2
	$('.select2').each(function() {
		$(this).select2({
			theme: $(this).data('theme') || 'default',
			placeholder: $(this).data('placeholder') || 'Select an option',
			allowClear: true
		});
	});

	const nameInput = document.getElementById('name');
	const slugInput = document.getElementById('slug');
	let manuallyEdited = false;

	// Function to generate slug from name
	function generateSlug(text) {
		return text
			.toLowerCase()
			.trim()
			// Replace spaces with hyphens
			.replace(/\s+/g, '-')
			// Remove special characters except hyphens
			.replace(/[^\w\-]+/g, '')
			// Replace multiple hyphens with single hyphen
			.replace(/\-\-+/g, '-')
			// Remove leading/trailing hyphens
			.replace(/^-+/, '')
			.replace(/-+$/, '');
	}

	// Auto-generate slug when name changes (only if not manually edited)
	if (nameInput && slugInput) {
		nameInput.addEventListener('input', function() {
			if (!manuallyEdited && !slugInput.value) {
				slugInput.value = generateSlug(this.value);
			}
		});

		// Mark as manually edited if user types in slug field
		slugInput.addEventListener('input', function() {
			manuallyEdited = true;
		});

		// If slug is cleared, allow auto-generation again
		slugInput.addEventListener('blur', function() {
			if (!this.value) {
				manuallyEdited = false;
			}
		});
	}
});
</script>
@stop
