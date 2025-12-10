@extends('layouts.app-tw')

@section('title', $entity->getTitleFormat())

@section('og-description', $entity->short)
@section('description', $entity->short)

@section('og-image')
@if ($photo = $entity->getPrimaryPhoto()){{ Storage::disk('external')->url($photo->getStoragePath()) }}@endif
@endsection

@section('content')

<!-- Back Button -->
<div class="mb-6">
	<a href="{{ URL::previous() }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm border rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
		<i class="bi bi-arrow-left"></i>
		<span>Back</span>
	</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
	
	<!-- Main Content -->
	<div class="xl:col-span-2 space-y-6">
		
		<!-- Header -->
		<div>
			<div class="flex items-start justify-between">
				<h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-4">{{ $entity->name }}</h1>
				
				<div class="flex items-center gap-2">
					@if ($signedIn)
						@if ($follow = $entity->followedBy($user))
						<a href="{!! route('entities.unfollow', ['id' => $entity->id]) !!}" 
							class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800" 
							title="Following - click to unfollow">
							<i class="bi bi-star-fill text-yellow-500 text-xl"></i>
						</a>
						@else
						<a href="{!! route('entities.follow', ['id' => $entity->id]) !!}" 
							class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800" 
							title="Click to follow">
							<i class="bi bi-star text-gray-400 text-xl"></i>
						</a>
						@endif
					@endif
					
					@if ($user && (Auth::user()->id === $entity->user?->id || $user->hasGroup('super_admin')))
					<div class="relative inline-block text-left">
						<button type="button" class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors" title="More actions">
							<i class="bi bi-three-dots text-xl"></i>
						</button>
					</div>
					@endif
				</div>
			</div>

			@if ($entity->entityStatus->name !== 'Active')
			<div class="mb-4">
				<span class="inline-flex items-center px-3 py-1 bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 rounded-lg text-sm border border-yellow-500/30">
					<i class="bi bi-exclamation-triangle mr-2"></i>
					{{ $entity->entityStatus->name }}
				</span>
			</div>
			@endif

			@if ($entity->short)
			<p class="text-xl text-gray-600 dark:text-gray-400">{{ $entity->short }}</p>
			@endif
		</div>

		<!-- Entity Image -->
		@if ($photo = $entity->getPrimaryPhoto())
		<div class="aspect-video relative overflow-hidden rounded-lg border border-dark-border bg-card shadow">
			<a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" 
				data-lightbox="entity-main" 
				class="block w-full h-full focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
				<img src="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" 
					 class="object-cover w-full h-full cursor-pointer hover:opacity-90 transition-opacity" 
					 alt="{{ $entity->name }}"
					 loading="lazy">
			</a>
		</div>
		@endif

		<!-- Description -->
		@if ($entity->description)
		<div class="rounded-lg border border-dark-border bg-card shadow">
			<div class="prose max-w-none p-8">
				{!! nl2br(e($entity->description)) !!}
			</div>
		</div>
		@endif

		<!-- Related Events -->
		<div class="rounded-lg border border-dark-border bg-card shadow p-6">
			<h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
				<i class="bi bi-calendar-event"></i>
				Related Events
			</h3>
			<a href="{{ url('events/related-to/'.$entity->slug) }}" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
				View All Related Events
			</a>
		</div>
	</div>

	<!-- Sidebar -->
	<div class="space-y-6">
		
		<!-- Entity Details Card -->
		<div class="rounded-lg border border-dark-border bg-card shadow">
			<div class="p-4 pt-2 space-y-4">
				
				<!-- Entity Type & Aliases -->
				<div class="space-y-2">
					@if ($entity->entityType)
					<div class="text-sm">
						<i class="bi bi-building mr-2"></i>
						<span class="font-bold text-gray-500 dark:text-gray-400">{{ $entity->entityType->name }}</span>
					</div>
					@endif

					@unless ($entity->aliases->isEmpty())
					<div class="text-sm">
						<span class="text-gray-500 dark:text-gray-400 font-medium">Aliases:</span>
						<div class="flex flex-wrap gap-1.5 mt-1">
							@foreach ($entity->aliases as $alias)
							<a href="/entities/alias/{{ $alias->name }}" class="badge-tw badge-secondary-tw text-xs hover:bg-dark-border">
								{{ $alias->name }}
							</a>
							@endforeach
						</div>
					</div>
					@endunless

					<!-- Followers -->
					<div class="text-sm text-gray-500 dark:text-gray-400">
						<i class="bi bi-people mr-2"></i>
						<span>{{ count($entity->follows) }} Followers</span>
					</div>
				</div>

				<!-- Roles -->
				@unless ($entity->roles->isEmpty())
				<div class="space-y-2">
					<div class="text-sm font-medium text-gray-500 dark:text-gray-400">Roles</div>
					<div class="flex flex-wrap gap-1.5">
						@foreach ($entity->roles as $role)
						<a href="/entities/role/{{ $role->name }}" class="badge-tw badge-primary-tw text-xs hover:bg-primary/30">
							{{ $role->name }}
						</a>
						@endforeach
					</div>
				</div>
				@endunless

				<!-- Series -->
				@unless ($entity->series->isEmpty())
				<div class="space-y-2">
					<div class="text-sm font-medium text-gray-500 dark:text-gray-400">Series</div>
					<div class="flex flex-wrap gap-1.5">
						@foreach ($entity->series as $series)
						<a href="/series/{{ $series->id }}" 
							class="badge-tw badge-primary-tw text-xs hover:bg-primary/30 {{ $series->visibility->name == 'Cancelled' ? 'line-through opacity-50' : '' }}">
							{{ $series->name }}
						</a>
						@endforeach
					</div>
				</div>
				@endunless

				<!-- Tags -->
				@unless ($entity->tags->isEmpty())
				<div class="space-y-2">
					<div class="text-sm font-medium text-gray-500 dark:text-gray-400">Tags</div>
					<div class="flex flex-wrap gap-1.5">
						@foreach ($entity->tags as $tag)
						<a href="/tags/{{ $tag->name }}" class="badge-tw badge-secondary-tw text-xs hover:bg-dark-border">
							{{ $tag->name }}
						</a>
						@endforeach
					</div>
				</div>
				@endunless

				<!-- Social Links -->
				@if ($entity->facebook_username || $entity->twitter_username || $entity->instagram_username)
				<div class="pt-3 border-t border-dark-border">
					<div class="flex items-center gap-3">
						@if ($entity->facebook_username)
						<a href="https://facebook.com/{{ $entity->facebook_username }}" 
							target="_blank"
							title="Facebook" 
							class="text-gray-400 hover:text-primary transition-colors">
							<i class="bi bi-facebook text-lg"></i>
						</a>
						@endif

						@if ($entity->twitter_username)
						<a href="https://twitter.com/{{ $entity->twitter_username }}" 
							target="_blank"
							title="Twitter" 
							class="text-gray-400 hover:text-primary transition-colors">
							<i class="bi bi-twitter text-lg"></i>
						</a>
						@endif

						@if ($entity->instagram_username)
						<a href="https://instagram.com/{{ $entity->instagram_username }}" 
							target="_blank"
							title="Instagram" 
							class="text-gray-400 hover:text-primary transition-colors">
							<i class="bi bi-instagram text-lg"></i>
						</a>
						@endif
					</div>
				</div>
				@endif
			</div>
		</div>

		<!-- Locations -->
		@unless ($entity->locations->isEmpty())
		<div class="rounded-lg border border-dark-border bg-card shadow p-6">
			<h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
				<i class="bi bi-geo-alt"></i>
				Locations
			</h3>
			<div class="space-y-4">
				@foreach ($entity->locations as $location)
				@if (isset($location->visibility) && ($location->visibility->name != 'Guarded' || ($location->visibility->name == 'Guarded' && $signedIn)))
				<div class="text-sm">
					<div class="font-medium text-gray-300 mb-1">
						{{ isset($location->locationType) ? $location->locationType->name : '' }}
					</div>
					<div class="text-gray-400">
						{{ $location->address_one }} 
						@if($location->neighborhood) {{ $location->neighborhood }} @endif
						<br>
						{{ $location->city }} {{ $location->state }} {{ $location->country }}
					</div>
					@if (isset($location->capacity) && $location->capacity !== 0)
					<div class="text-gray-400 mt-1">
						<span class="font-medium">Capacity:</span> {{ $location->capacity }}
					</div>
					@endif
					<div class="mt-2 flex gap-2">
						@if (isset($location->map_url) && $location->map_url != '')
						<a href="{!! $location->map_url !!}" 
							target="_blank" 
							class="text-primary hover:text-primary-hover transition-colors"
							title="View on map">
							<i class="bi bi-geo-alt-fill"></i>
						</a>
						@endif
						@if ($signedIn && ($entity->ownedBy($user) || $user->hasGroup('super_admin')))
						<a href="{!! route('entities.locations.edit', ['entity' => $entity->slug, 'location' => $location->id]) !!}" 
							class="text-gray-400 hover:text-primary transition-colors"
							title="Edit location">
							<i class="bi bi-pencil"></i>
						</a>
						@endif
					</div>
				</div>
				@endif
				@endforeach
			</div>
			@if ($user && Auth::user()->id == ($entity->user ? $entity->user?->id : null))
			<div class="mt-4">
				<a href="{!! route('entities.locations.create', ['entity' => $entity->slug]) !!}" 
					class="inline-flex items-center px-3 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors text-sm">
					<i class="bi bi-plus-lg mr-2"></i>
					Add Location
				</a>
			</div>
			@endif
		</div>
		@endunless

		<!-- Contacts -->
		@unless ($entity->contacts->isEmpty())
		<div class="rounded-lg border border-dark-border bg-card shadow p-6">
			<h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
				<i class="bi bi-person"></i>
				Contacts
			</h3>
			<div class="space-y-3">
				@foreach ($entity->contacts as $contact)
				<div class="text-sm">
					<div class="font-medium text-gray-300">{{ $contact->name }}</div>
					@if ($contact->email)
					<a href="mailto:{{ $contact->email }}" class="text-primary hover:text-primary-hover">
						{{ $contact->email }}
					</a>
					@endif
					@if ($contact->phone)
					<div class="text-gray-400">{{ $contact->phone }}</div>
					@endif
					@if ($signedIn && $entity->ownedBy($user))
					<a href="{!! route('entities.contacts.edit', ['entity' => $entity->slug, 'contact' => $contact->id]) !!}" 
						class="text-gray-400 hover:text-primary transition-colors"
						title="Edit contact">
						<i class="bi bi-pencil"></i>
					</a>
					@endif
				</div>
				@endforeach
			</div>
			@if ($user && ((Auth::user()->id == ($entity->user ? $entity->user?->id : null)) || $user->hasGroup('super_admin')))
			<div class="mt-4">
				<a href="{!! route('entities.contacts.create', ['entity' => $entity->slug]) !!}" 
					class="inline-flex items-center px-3 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors text-sm">
					<i class="bi bi-plus-lg mr-2"></i>
					Add Contact
				</a>
			</div>
			@endif
		</div>
		@endunless

		<!-- Links -->
		@unless ($entity->links->isEmpty())
		<div class="rounded-lg border border-dark-border bg-card shadow p-6">
			<h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
				<i class="bi bi-link-45deg"></i>
				Links
			</h3>
			<div class="space-y-2">
				@foreach ($entity->links as $link)
				<div class="flex items-center gap-2">
					<a href="{{ $link->url }}" 
						target="_blank"
						class="text-primary hover:text-primary-hover text-sm break-all">
						{{ $link->text ?? $link->url }}
					</a>
					<i class="bi bi-box-arrow-up-right text-xs text-gray-400"></i>
					@if ($signedIn && $entity->ownedBy($user))
					<a href="{!! route('entities.links.edit', ['entity' => $entity->slug, 'link' => $link->id]) !!}" 
						class="text-gray-400 hover:text-primary transition-colors ml-auto"
						title="Edit link">
						<i class="bi bi-pencil"></i>
					</a>
					@endif
				</div>
				@endforeach
			</div>
			@if ($user && Auth::user()->id == ($entity->user ? $entity->user?->id : null))
			<div class="mt-4">
				<a href="{!! route('entities.links.create', ['entity' => $entity->slug]) !!}" 
					class="inline-flex items-center px-3 py-2 bg-dark-card border border-dark-border text-white rounded-lg hover:bg-dark-border transition-colors text-sm">
					<i class="bi bi-plus-lg mr-2"></i>
					Add Link
				</a>
			</div>
			@endif
		</div>
		@endunless

		<!-- Photos Section -->
		@include('partials.photo-gallery-tw', ['entity' => $entity, 'lightboxGroup' => 'entity-gallery'])

		<!-- Photo Upload -->
		@if ($user && (Auth::user()->id == $entity->user?->id || $user->hasGroup('super_admin')))
		<div class="rounded-lg border border-dark-border bg-card shadow p-4">
			<form action="/entities/{{ $entity->id }}/photos" class="dropzone border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-4 text-center cursor-pointer hover:border-gray-400 dark:hover:border-gray-600 transition-colors" id="myDropzone" method="POST">
				<input type="hidden" name="_token" value="{{ csrf_token() }}">
				<i class="bi bi-cloud-upload text-3xl text-gray-400 mb-2"></i>
				<p class="text-sm text-gray-500 dark:text-gray-400">Add a picture (Max size 5MB)</p>
			</form>
		</div>
		@endif
	</div>
</div>

@stop

@section('scripts.footer')

@if ($user && (Auth::user()->id === $entity->user?->id || $user->hasGroup('super_admin')))
<script>
window.Dropzone.autoDiscover = true;
$(document).ready(function(){

	var myDropzone = new window.Dropzone('#myDropzone', {
        dictDefaultMessage: "Add a picture (Max size 5MB)"
    });

    $('div.dz-default.dz-message').css({'color': '#9ca3af', 'opacity': 1, 'background-image': 'none'});

	myDropzone.options.addPhotosForm = {
		maxFilesize: 5,
		accept: ['.jpg','.png','.gif'],
        dictDefaultMessage: "Drop a file here to add a picture",
		init: function () {
				myDropzone.on("success", function (file) {
	                location.href = '/entities/{{ $entity->slug }}';
	                location.reload();
	            });
	            myDropzone.on("successmultiple", function (file) {
	                location.href = '/entities/{{ $entity->slug }}';
	                location.reload();
	            });
				myDropzone.on("error", function (file, message) {
					console.log(message)
					Swal.fire({
						title: "Error",
						text: "Error: "+message.message,
						icon: "error",
						confirmButtonColor: "#6366f1",
						confirmButtonText: "Ok",
				}).then(result => {
					location.href = '/entities/{{ $entity->slug }}';
	                location.reload();
					});
				});
	        },
		success: console.log('Upload successful')
	};

	myDropzone.options.addPhotosForm.init();

})
</script>
@endif

@stop
