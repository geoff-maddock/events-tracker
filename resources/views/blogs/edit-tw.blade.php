@extends('layouts.app-tw')

@section('title', 'Edit Blog')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
	<!-- Page Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-primary mb-2">Edit Blog</h1>
		<p class="text-muted-foreground">{{ $blog->name }}</p>
	</div>

	<!-- Form Card -->
	<div class="card-tw mb-6">
		<div class="p-6">
			<form action="{{ route('blogs.update', $blog->slug) }}" method="POST">
				@csrf
				@method('PATCH')

				@include('blogs.form-tw', ['action' => 'update'])
			</form>
		</div>
	</div>

	<!-- Delete Section -->
	<div class="card-tw border-destructive/20">
		<div class="p-6">
			<h2 class="text-lg font-semibold text-destructive mb-2">Danger Zone</h2>
			<p class="text-sm text-muted-foreground mb-4">Once you delete a blog, there is no going back. Please be certain.</p>

			<form action="{{ route('blogs.destroy', $blog->slug) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this blog? This action cannot be undone.');">
				@csrf
				@method('DELETE')
				<button type="submit" class="inline-flex items-center px-4 py-2 bg-destructive text-destructive-foreground rounded-lg hover:bg-destructive/90 transition-colors">
					<i class="bi bi-trash mr-2"></i>
					Delete Blog
				</button>
			</form>
		</div>
	</div>
</div>

@stop

@section('scripts.footer')
<script>
$(document).ready(function() {
	// Initialize Select2 for tags and entities
	$('#tag_list').select2({
		theme: 'tailwind',
		width: '100%',
		placeholder: 'Choose a tag',
		tags: true
	});

	$('#entity_list').select2({
		theme: 'tailwind',
		width: '100%',
		placeholder: 'Choose a related artist, producer, dj',
		tags: false
	});
});
</script>
@stop
