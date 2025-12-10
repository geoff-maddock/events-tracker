@php
	// Accept either 'photos', 'event', or 'entity' parameter
	$galleryPhotos = $photos ?? collect();
	
	// If an event is provided, gather all photos (event photos + entity primary photos)
	if (isset($event)) {
		$eventPhotos = $event->photos;
		$entityPhotos = $event->entities->flatMap->photos->where('is_primary', true);
		$galleryPhotos = $eventPhotos->concat($entityPhotos);
	}
	
	// If an entity is provided, use entity photos
	if (isset($entity)) {
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
		</div>
		@endforeach
	</div>
</div>
@endif
