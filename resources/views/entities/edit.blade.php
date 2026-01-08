@extends('layouts.app-tw')

@section('title', 'Entity Edit')

@section('select2.include')
<!-- Select2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
@endsection

@section('content')

<div class="max-w-7xl mx-auto">
	<!-- Header -->
	<div class="mb-6">
		<h1 class="text-3xl font-bold text-foreground mb-2">Edit Entity</h1>
		<div class="text-sm text-muted-foreground">
			@include('events.crumbs', ['slug' => $entity->name ?: $entity->id])
		</div>
	</div>

	<!-- Action Buttons -->
	<div class="mb-6 flex gap-2">
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
					@csrf
					@method('PATCH')

					@include('entities.form', ['action' => 'update'])
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
		</div>

		<!-- Photos Sidebar -->
		<div class="lg:col-span-1">
			<div class="bg-card rounded-lg border border-border shadow-sm p-4 space-y-4">
				<h3 class="font-semibold text-lg text-foreground">Entity Photos</h3>

				<!-- Photo Gallery -->
				<div class="grid grid-cols-2 gap-2">
					@foreach ($entity->photos as $photo)
					<div class="relative group">
						<a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}"
							data-lightbox="entity-photos"
							class="block aspect-square rounded-lg overflow-hidden border border-border">
							<img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}"
								alt="{{ $entity->name}}"
								class="w-full h-full object-cover group-hover:scale-105 transition-transform">
						</a>

						@if ($user && ($entity->user && (Auth::user()->id === $entity->user->id) || $user->hasGroup('super_admin')))
						<div class="absolute top-1 right-1 flex gap-1">
							<!-- Delete Photo -->
							<form method="POST" action="{{ route('photos.destroy', $photo->id) }}" class="inline">
								@csrf
								@method('DELETE')
								<button type="submit"
									onclick="return confirm('Delete this photo?')"
									class="p-1 bg-destructive/80 text-destructive-foreground rounded hover:bg-destructive transition-colors"
									title="Delete photo">
									<i class="bi bi-trash text-xs"></i>
								</button>
							</form>

							<!-- Set/Unset Primary -->
							@if ($photo->is_primary)
							<form method="POST" action="/photos/{{ $photo->id }}/unset-primary" class="inline">
								@csrf
								<button type="submit"
									class="p-1 bg-primary/80 text-primary-foreground rounded hover:bg-primary transition-colors"
									title="Primary Photo (Click to unset)">
									<i class="bi bi-star-fill text-xs"></i>
								</button>
							</form>
							@else
							<form method="POST" action="/photos/{{ $photo->id }}/set-primary" class="inline">
								@csrf
								<button type="submit"
									class="p-1 bg-muted/80 text-muted-foreground rounded hover:bg-accent transition-colors"
									title="Set as primary photo">
									<i class="bi bi-star text-xs"></i>
								</button>
							</form>
							@endif
						</div>
						@endif
					</div>
					@endforeach
				</div>

				<!-- Photo Upload Dropzone -->
				@if ($user && ($entity->user && (Auth::user()->id === $entity->user->id) || $user->hasGroup('super_admin')))
				<div class="pt-4 border-t border-border">
					<form action="/entities/{{ $entity->id }}/photos"
						class="dropzone border-2 border-dashed border-border rounded-lg p-4 text-center cursor-pointer hover:border-muted-foreground/60 transition-colors"
						id="myDropzone"
						method="POST">
						<input type="hidden" name="_token" value="{{ csrf_token() }}">
					</form>
				</div>
				@endif
			</div>
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
