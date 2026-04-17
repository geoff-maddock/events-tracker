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

	<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
		<!-- Main Form -->
		<div class="lg:col-span-2">
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

		<!-- Photos Sidebar -->
		<div class="lg:col-span-1">
			<!-- Photo Upload -->
			<div class="rounded-lg border border-border bg-card shadow p-4 mb-6">
				<form action="/blogs/{{ $blog->id }}/photos"
					class="dropzone border-2 border-dashed border-border rounded-lg p-4 text-center cursor-pointer hover:border-muted-foreground/60 transition-colors"
					id="myDropzone"
					method="POST">
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
				</form>
			</div>

			<!-- Photos Section -->
			@include('partials.photo-gallery-tw', ['blog' => $blog, 'event' => null, 'entity' => null, 'series' => null, 'lightboxGroup' => 'blog-gallery'])
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

	// Wait for Dropzone to be available
	var attempts = 0;
	var maxAttempts = 50;

	function initDropzone() {
		attempts++;

		if (typeof window.Dropzone === 'undefined') {
			if (attempts >= maxAttempts) {
				console.error('Dropzone failed to load after ' + (maxAttempts * 100) + 'ms');
				return;
			}
			setTimeout(initDropzone, 100);
			return;
		}

		window.Dropzone.autoDiscover = false;
		var myDropzone = new window.Dropzone('#myDropzone', {
			dictDefaultMessage: "Add a picture (Max size 5MB)"
		});

		$('div.dz-default.dz-message').css({'color': '#9ca3af', 'opacity': 1, 'background-image': 'none'});

		myDropzone.options.addPhotosForm = {
			maxFilesize: 5,
			acceptedFiles: '.jpg,.jpeg,.png,.gif,.webp',
			dictDefaultMessage: "Drop a file here to add a picture",
			init: function () {
				myDropzone.on("success", function (file) {
					location.reload();
				});
				myDropzone.on("successmultiple", function (file) {
					location.reload();
				});
				myDropzone.on("error", function (file, message) {
					console.log(message);
				});
			},
			success: function() { console.log('Upload successful'); }
		};

		myDropzone.options.addPhotosForm.init();
	}

	initDropzone();
});
</script>
@stop
