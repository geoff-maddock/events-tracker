@extends('layouts.app-tw')

@section('title', 'Blog: ' . $blog->name)
@section('description', \Illuminate\Support\Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags($blog->body ?? ''))), 160, '...'))
@section('og-image', url('/images/arcane-city-promo.jpg'))

@section('content')

@include('blogs.json-ld', ['blog' => $blog])

<div class="container mx-auto max-w-4xl">
	<!-- Header with breadcrumbs -->
	<div class="mb-6">
		<div class="flex items-center gap-2 text-sm text-muted-foreground mb-2">
			<a href="{{ route('blogs.index') }}" class="hover:text-primary transition-colors">Blogs</a>
			<i class="bi bi-chevron-right text-xs"></i>
			<span class="text-foreground">{{ $blog->name }}</span>
		</div>
		<h1 class="text-3xl font-bold text-foreground">{{ $blog->name }}</h1>
		@if ($blog->slug)
			<p class="text-sm text-muted-foreground mt-1">{{ $blog->slug }}</p>
		@endif
	</div>

	<!-- Action Buttons -->
	<div class="flex flex-wrap gap-2 mb-6">
		@can('edit_blog')
			<a href="{{ route('blogs.edit', ['blog' => $blog->slug]) }}" class="inline-flex items-center px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors">
				<i class="bi bi-pencil mr-2"></i>
				Edit Blog
			</a>
		@endcan
		<a href="{{ route('blogs.index') }}" class="inline-flex items-center px-4 py-2 bg-muted text-muted-foreground rounded-lg hover:bg-muted/80 transition-colors">
			<i class="bi bi-arrow-left mr-2"></i>
			Return to list
		</a>
	</div>

	<!-- Blog Content Card -->
	<div class="card-tw mb-6">
		<div class="p-6">
			@if ($blog->body)
				<div class="prose prose-slate dark:prose-invert max-w-none">
					@if (auth()->check() && auth()->user()->can('trust_blog'))
						{!! $blog->body !!}
					@else
						{!! nl2br(e($blog->body)) !!}
					@endif
				</div>
			@endif

			<!-- Tags -->
			@unless ($blog->tags->isEmpty())
				<div class="mt-6 pt-6 border-t border-border">
					<div class="flex flex-wrap items-center gap-2">
						<span class="text-sm font-semibold text-foreground">Tags:</span>
						@foreach ($blog->tags as $tag)
							<a href="/tags/{{ $tag->slug }}" class="badge-tw badge-primary-tw">
								{{ $tag->name }}
							</a>
						@endforeach
					</div>
				</div>
			@endunless

			<!-- Entities -->
			@unless ($blog->entities->isEmpty())
				<div class="mt-4 pt-4 border-t border-border">
					<div class="flex flex-wrap items-center gap-2">
						<span class="text-sm font-semibold text-foreground">Entities:</span>
						@foreach ($blog->entities as $entity)
							<x-entity-badge :entity="$entity" context="blogs" variant="secondary" />
						@endforeach
					</div>
				</div>
			@endunless

			<!-- Metadata -->
			<div class="mt-6 pt-6 border-t border-border text-sm text-muted-foreground">
				<div class="flex flex-wrap gap-4">
					@if ($blog->created_by)
						<div>
							<span class="font-medium">Created by:</span>
							@if ($blog->user)
								<a href="/users/{{ $blog->user->id }}" class="text-primary hover:underline">{{ $blog->user->name }}</a>
							@else
								User #{{ $blog->created_by }}
							@endif
						</div>
					@endif
					@if ($blog->created_at)
						<div>
							<span class="font-medium">Created:</span>
							{{ $blog->created_at->format('F j, Y g:i A') }}
						</div>
					@endif
					@if ($blog->updated_at && $blog->updated_at != $blog->created_at)
						<div>
							<span class="font-medium">Updated:</span>
							{{ $blog->updated_at->format('F j, Y g:i A') }}
						</div>
					@endif
				</div>
			</div>

			<!-- Delete Form -->
			@can('edit_blog')
				<div class="mt-6 pt-6 border-t border-border">
					{!! delete_form(['blogs.destroy', $blog->slug]) !!}
				</div>
			@endcan
		</div>
	</div>

	<!-- Photo Upload -->
	@auth
		@if (Auth::user()->id === $blog->user?->id || Auth::user()->hasGroup('super_admin'))
		<div class="rounded-lg border border-border bg-card shadow p-4 mb-6">
			<form action="/blogs/{{ $blog->id }}/photos"
				class="dropzone border-2 border-dashed border-border rounded-lg p-4 text-center cursor-pointer hover:border-muted-foreground/60 transition-colors"
				id="myDropzone"
				method="POST">
				<input type="hidden" name="_token" value="{{ csrf_token() }}">
			</form>
		</div>
		@endif
	@endauth

	<!-- Photos Section -->
	@include('partials.photo-gallery-tw', ['blog' => $blog, 'event' => null, 'entity' => null, 'series' => null, 'lightboxGroup' => 'blog-gallery'])
</div>

@stop

@section('scripts.footer')
@auth
	@if (Auth::user()->id === $blog->user?->id || Auth::user()->hasGroup('super_admin'))
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

	// Start trying to initialize Dropzone
	initDropzone();
})
</script>
	@endif
@endauth
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	const deleteButton = document.querySelector('input.delete');
	if (deleteButton) {
		deleteButton.addEventListener('click', function(e) {
			e.preventDefault();
			const form = this.closest('form');
			Swal.fire({
				title: "Are you sure?",
				text: "You will not be able to recover this blog!",
				icon: "warning",
				showCancelButton: true,
				confirmButtonColor: "#DD6B55",
				confirmButtonText: "Yes, delete it!",
				preConfirm: function() {
					return new Promise(function(resolve) {
						setTimeout(function() {
							resolve()
						}, 2000)
					})
				}
			}).then(result => {
				if (result.value) {
					form.submit();
				} else {
					console.log('cancelled confirm')
				}
			});
		});
	}
});
</script>
@stop
