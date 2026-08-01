{{-- Venue facts card: quick-scan capacity/address/ages/links/photos for venue entity pages.
     Included in the sidebar above the Entity Details card, venue entities only. --}}
@php
	$venueFactsLocation = $entity->getPrimaryLocation();
	// Match the Locations card's Guarded gating (show-tw.blade.php's Locations card):
	// a Guarded location's address/capacity are only shown to signed-in users.
	$venueFactsLocationVisible = $venueFactsLocation
		&& isset($venueFactsLocation->visibility)
		&& ($venueFactsLocation->visibility->name != 'Guarded' || $signedIn);
	$venueFactsAgePolicy = $entity->getAgePolicy();
	$venueFactsWebsite = $entity->primaryLink();
	$venueFactsPhotoCount = $entity->photos->count();
	$venueFactsHasSocial = $entity->facebook_username || $entity->twitter_username || $entity->instagram_username;
@endphp

<div class="rounded-lg border border-border bg-card shadow p-6">
	<h2 class="text-xl font-semibold mb-4 flex items-center gap-2">
		<i class="bi bi-clipboard-data"></i>
		Venue Facts
	</h2>

	<dl class="space-y-4 text-sm">
		@if ($venueFactsLocationVisible && !empty($venueFactsLocation->capacity))
		<div>
			<dt class="font-medium text-muted-foreground">Capacity</dt>
			<dd class="text-foreground mt-0.5">{{ $venueFactsLocation->capacity }}</dd>
		</div>
		@endif

		@if ($venueFactsLocationVisible && !empty($venueFactsLocation->address_one))
		<div>
			<dt class="font-medium text-muted-foreground">Address</dt>
			<dd class="text-foreground mt-0.5">
				{{ $venueFactsLocation->address_one }}
				@if ($venueFactsLocation->neighborhood)
					&middot; {{ $venueFactsLocation->neighborhood }}
				@endif
				<br>
				{{ $venueFactsLocation->city }}@if ($venueFactsLocation->state), {{ $venueFactsLocation->state }}@endif
			</dd>
		</div>
		@endif

		@if ($venueFactsAgePolicy)
		<div>
			<dt class="font-medium text-muted-foreground">Ages</dt>
			<dd class="text-foreground mt-0.5">{{ $venueFactsAgePolicy }}</dd>
		</div>
		@endif

		@if ($venueFactsWebsite || $venueFactsHasSocial)
		<div>
			<dt class="font-medium text-muted-foreground">Links</dt>
			<dd class="text-foreground mt-1 flex flex-wrap gap-3">
				@if ($venueFactsWebsite)
				<a href="{{ $venueFactsWebsite->url }}" target="_blank" class="text-primary hover:text-primary/90">Website</a>
				@endif
				@if ($entity->facebook_username)
				<a href="https://facebook.com/{{ $entity->facebook_username }}" target="_blank" class="text-primary hover:text-primary/90">Facebook</a>
				@endif
				@if ($entity->twitter_username)
				<a href="https://twitter.com/{{ $entity->twitter_username }}" target="_blank" class="text-primary hover:text-primary/90">Twitter / X</a>
				@endif
				@if ($entity->instagram_username)
				<a href="https://instagram.com/{{ $entity->instagram_username }}" target="_blank" class="text-primary hover:text-primary/90">Instagram</a>
				@endif
			</dd>
		</div>
		@endif

		@if ($venueFactsPhotoCount > 0)
		<div>
			<dt class="font-medium text-muted-foreground">Photos</dt>
			<dd class="text-foreground mt-0.5">
				<a href="#photo-gallery" class="text-primary hover:text-primary/90">
					{{ $venueFactsPhotoCount }} {{ Str::plural('photo', $venueFactsPhotoCount) }}
				</a>
			</dd>
		</div>
		@endif
	</dl>
</div>
