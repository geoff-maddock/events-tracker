@extends('layouts.app-tw')

@section('title', 'Entity Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="w-full">
	<!-- Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-foreground mb-2">Edit Entity <span class="text-muted-foreground">{{ $entity->name }}</span></h1>
	</div>

	<!-- Actions Menu -->
	<div class="mb-6 flex flex-wrap gap-2">
		<x-ui.button variant="default" href="{{ route('entities.show', $entity->slug) }}">
			<i class="bi bi-eye mr-2"></i>
			Show Entity
		</x-ui.button>
		<x-ui.button variant="outline" href="{{ route('entities.index') }}">
			<i class="bi bi-list mr-2"></i>
			Return to list
		</x-ui.button>
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
		<!-- Main Form Column -->
		<div class="lg:col-span-2">
			<div class="bg-card rounded-lg border border-border shadow-sm p-6">
				<form method="POST" action="{{ route('entities.update', $entity->slug) }}" class="space-y-6">
					@include('entities.form', ['action' => 'update'])
					@csrf
					@method('PATCH')
				</form>

				<!-- Delete Button -->
				@if ($user && ($entity->ownedBy($user) || $user->hasGroup('super_admin')))
				<div class="mt-6 pt-6 border-t border-border">
					<form method="POST" action="{{ route('entities.destroy', $entity->slug) }}" onsubmit="return confirm('Are you sure you want to delete this entity? This action cannot be undone.');">
						@csrf
						@method('DELETE')
						<button type="submit" class="inline-flex items-center px-4 py-2 bg-destructive text-destructive-foreground rounded-md hover:bg-destructive/90 transition-colors">
							<i class="bi bi-trash mr-2"></i>
							Delete Entity
						</button>
					</form>
				</div>
				@endif
			</div>

			<!-- Back Button -->
			<div class="mt-6">
				<x-ui.button variant="ghost" href="{{ route('entities.show', $entity->slug) }}">
					<i class="bi bi-arrow-left mr-2"></i>
					Back to Entity
				</x-ui.button>
			</div>
		</div>

		<!-- Photos Sidebar -->
		<div class="lg:col-span-1">
			<!-- Photo Upload -->
			@if ($user && ($entity->user && (Auth::user()->id === $entity->user->id) || $user->hasGroup('super_admin')))
			<div class="rounded-lg border border-border bg-card shadow p-2 pt-2 space-y-4 mb-6">
				<form action="/entities/{{ $entity->id }}/photos"
					class="dropzone border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-4 text-center cursor-pointer hover:border-gray-400 dark:hover:border-gray-600 transition-colors"
					id="myDropzone"
					method="POST">
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
				</form>
			</div>
			@endif

			<!-- Photos Section -->
			@include('partials.photo-gallery-tw', ['entity' => $entity, 'lightboxGroup' => 'entity-gallery'])
		</div>
	</div>
</div>

@stop

@section('scripts.footer')
@if ($user && ($entity->user && (Auth::user()->id === $entity->user->id) || $user->hasGroup('super_admin')))
<script>
$(document).ready(function(){
	// Wait for Dropzone to be available
	var attempts = 0;
	var maxAttempts = 50; // 5 seconds max

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

		console.log('Dropzone loaded successfully!');
		window.Dropzone.autoDiscover = false;
		var myDropzone = new window.Dropzone('#myDropzone', {
			dictDefaultMessage: "Drop a file here to add an entity profile picture. (Max size 5MB)"
		});

		$('div.dz-default.dz-message').css({'color': '#9ca3af', 'opacity': 1, 'background-image': 'none'});

		myDropzone.options.addPhotosForm = {
			maxFilesize: 5,
			accept: ['.jpg','.png','.gif'],
			dictDefaultMessage: "Drop a file here to add a picture",
			init: function () {
				myDropzone.on("success", function (file) {
					location.reload();
				});
				myDropzone.on("successmultiple", function (file) {
					location.reload();
				});
				myDropzone.on("error", function (file, message) {
					Swal.fire({
						title: "Error",
						text: "Error: " + message.message,
						icon: "error",
						confirmButtonColor: "#ef4444",
						confirmButtonText: "Ok",
					}).then(result => {
						location.reload();
					});
				});
			},
			success: console.log('Upload successful')
		};

		myDropzone.options.addPhotosForm.init();
	}

	// Start trying to initialize Dropzone
	initDropzone();
});
</script>
@endif
@stop
