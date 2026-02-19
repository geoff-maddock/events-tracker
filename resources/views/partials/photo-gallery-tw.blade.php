@php
	// Accept either 'photos', 'event', 'series', or 'entity' parameter
	$galleryPhotos = $photos ?? collect();

	// Determine owner for admin controls
	$galleryOwner = $entity ?? $series ?? $event ?? null;

	// null means all displayed photos are manageable; a collection restricts which are
	$manageablePhotoIds = null;

	// If an event is provided, gather all photos (event photos + entity primary photos)
	if (isset($event)) {
		$eventPhotos = $event->photos;
		$entityPhotos = $event->entities->flatMap(function($ent) {
			return $ent->photos->where('is_primary', true);
		});
		$galleryPhotos = $eventPhotos->concat($entityPhotos);
		// Only the event's own photos should have admin controls
		$manageablePhotoIds = $eventPhotos->pluck('id');
	}
	// If a series is provided, use series photos only
	elseif (isset($series)) {
		$galleryPhotos = $series->photos;
	}
	// If an entity is provided, use entity photos
	elseif (isset($entity)) {
		$galleryPhotos = $entity->photos;
	}
@endphp

@if ($galleryPhotos->count() > 0)
<div class="rounded-lg border border-dark-border bg-card shadow p-8 p-4 pt-4 space-y-4">
	<h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
		<i class="bi bi-images"></i>
		Photos
	</h3>
	<div class="flex flex-wrap gap-4">
		@foreach ($galleryPhotos as $photo)
		<div class="relative group">
			<a href="{{ Storage::disk('external')->url($photo->getStoragePath()) }}" 
			   data-lightbox="{{ $lightboxGroup ?? 'photo-gallery' }}" 
			   class="block focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded">
				<img src="{{ Storage::disk('external')->url($photo->getStorageThumbnail()) }}" 
					 alt="{{ $photo->name ?? 'Photo' }}"
					 class="w-32 h-32 object-cover rounded shadow hover:scale-105 transition-transform cursor-pointer"
					 loading="lazy">
			</a>

			{{-- Admin Controls --}}
			@if (isset($galleryOwner) && Auth::check() && (Auth::user()->id == $galleryOwner->user?->id || Auth::user()->hasGroup('super_admin')) && ($manageablePhotoIds === null || $manageablePhotoIds->contains($photo->id)))
			<div class="absolute top-2 right-2 flex flex-col gap-2 z-50">
				{{-- Delete --}}
				<form method="POST" action="/photos/{{ $photo->id }}" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this photo?');">
					@csrf
					@method('DELETE')
					<button type="submit" class="p-2 bg-red-600 text-white rounded shadow-md hover:bg-red-700 transition-colors" title="Delete photo">
						<i class="bi bi-trash-fill text-sm"></i>
					</button>
				</form>

				{{-- Primary Toggle --}}
				@if ($photo->is_primary)
				<form method="POST" action="/photos/{{ $photo->id }}/unset-primary" class="inline-block">
					@csrf
					<button type="submit" class="p-2 bg-yellow-500 text-white rounded shadow-md hover:bg-yellow-600 transition-colors" title="Unset primary">
						<i class="bi bi-star-fill text-sm"></i>
					</button>
				</form>
				@else
				<form method="POST" action="/photos/{{ $photo->id }}/set-primary" class="inline-block">
					@csrf
					<button type="submit" class="p-2 bg-gray-800/80 text-gray-300 hover:text-white rounded shadow-md hover:bg-gray-800 transition-colors border border-gray-600" title="Set as primary">
						<i class="bi bi-star text-sm"></i>
					</button>
				</form>
				@endif
			</div>
			@endif
		</div>
		@endforeach
	</div>
</div>
@endif
