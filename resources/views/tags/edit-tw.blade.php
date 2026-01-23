@extends('layouts.app-tw')

@section('title', 'Edit Keyword Tag')

@section('content')

<div class="max-w-4xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Edit Keyword Tag</h1>
		<p class="text-muted-foreground">{{ $tag->name }}</p>
	</div>

	<!-- Action Menu -->
	<div class="flex flex-wrap gap-3 mb-6">
		<a href="{{ route('tags.show', ['tag' => $tag->slug]) }}" class="inline-flex items-center px-4 py-2 bg-accent text-foreground border border-border rounded-lg hover:bg-accent/80 transition-colors">
			<i class="bi bi-eye mr-2"></i>
			Show Tag
		</a>
		<a href="{{ route('tags.index') }}" class="inline-flex items-center px-4 py-2 bg-card border border-border text-foreground rounded-lg hover:bg-accent transition-colors">
			<i class="bi bi-arrow-left mr-2"></i>
			Return to list
		</a>
	</div>

	<!-- Form Card -->
	<div class="card-tw mb-6">
		<div class="p-6">
			<form action="{{ route('tags.update', $tag->slug) }}" method="POST">
				@csrf
				@method('PATCH')

				@include('tags.form-tw', ['action' => 'update'])
			</form>
		</div>
	</div>

	<!-- Delete Section -->
	<div class="card-tw border-destructive/20">
		<div class="p-6">
			<h2 class="text-lg font-semibold text-destructive mb-2">Danger Zone</h2>
			<p class="text-sm text-muted-foreground mb-4">Once you delete a tag, there is no going back. Please be certain.</p>

			<form action="{{ route('tags.destroy', $tag->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this tag? This action cannot be undone.');">
				@csrf
				@method('DELETE')
				<button type="submit" class="inline-flex items-center px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:bg-destructive/90 transition-colors">
					<i class="bi bi-trash mr-2"></i>
					Delete Tag
				</button>
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
	let manuallyEdited = !!slugInput.value; // Consider it manually edited if there's already a value

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

	// Auto-generate slug when name changes (only if user clears slug field)
	if (nameInput && slugInput) {
		nameInput.addEventListener('input', function() {
			if (!manuallyEdited || !slugInput.value) {
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
