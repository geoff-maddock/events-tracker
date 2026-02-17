<!-- Entity Card Component -->
<article class="entity-card-tw group {{ $entity->entityStatus->name === 'Inactive' ? 'opacity-50' : '' }}" id="entity-card-{{ $entity->id }}">
	<!-- Entity Image -->
	<div class="relative overflow-hidden">
		@if ($primary = $entity->getPrimaryPhoto())
		<a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
			data-title="{{ $entity->name }}"
			data-lightbox="{{ $primary->path }}">
			<img src="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
				alt="{{ $entity->name }}"
				class="w-full aspect-square object-cover group-hover:scale-105 transition-transform duration-300">
		</a>
		@else
		<a href="/images/entity-placeholder.png"
			data-lightbox="entity-{{ $entity->id }}">
			<div class="w-full aspect-square bg-card flex items-center justify-center">
				<i class="bi bi-building text-4xl text-muted-foreground/60"></i>
			</div>
		</a>
		@endif

		<!-- Follow Button -->
		<div class="absolute top-2 right-2">
			@if ($signedIn)
				@if ($follow = $entity->followedBy($user))
				<a href="{!! route('entities.unfollow', ['id' => $entity->id]) !!}"
					data-target="#entity-card-{{ $entity->id }}"
					class="ajax-action p-2 bg-background/80 rounded-full hover:bg-background transition-colors"
					title="Following - click to unfollow">
					<i class="bi bi-check-circle-fill text-primary text-lg"></i>
				</a>
				@else
				<a href="{!! route('entities.follow', ['id' => $entity->id]) !!}"
					data-target="#entity-card-{{ $entity->id }}"
					class="ajax-action p-2 bg-background/80 rounded-full hover:bg-background transition-colors"
					title="Click to follow">
					<i class="bi bi-plus-circle text-muted-foreground hover:text-primary text-lg"></i>
				</a>
				@endif
			@else
				<a href="{!! route('login') !!}"
					class="p-2 bg-background/80 rounded-full hover:bg-background transition-colors"
					title="Sign in to follow">
					<i class="bi bi-plus-circle text-muted-foreground hover:text-primary text-lg"></i>
				</a>
			@endif
		</div>

		<!-- Inactive Badge -->
		@if ($entity->entityStatus->name === 'Inactive')
		<div class="absolute top-2 left-2">
			<span class="badge-tw bg-gray-500/80 text-white">Inactive</span>
		</div>
		@endif
	</div>

	<!-- Card Content -->
	<div class="p-4 space-y-3">
		<!-- Entity Name -->
		<h3 class="text-lg font-semibold line-clamp-2">
			<a href="{!! route('entities.show', ['entity' => $entity->slug]) !!}" 
				class="hover:text-primary transition-colors">
				{{ $entity->name }}
			</a>
		</h3>

		<!-- Entity Type & Location -->
		@if ($type = $entity->entityType)
		<div class="text-sm text-muted-foreground">
			<i class="bi bi-building mr-1"></i>
			<span class="font-medium">{{ $entity->entityType->name }}</span>
		</div>
		@endif

		@if ($entity->getPrimaryLocationAddress())
		<div class="text-sm text-muted-foreground flex items-start gap-2">
			<i class="bi bi-geo-alt mt-0.5 flex-shrink-0"></i>
			<span class="line-clamp-2">
				{{ $entity->getPrimaryLocationAddress() }}
				@if($entity->getPrimaryLocation()->neighborhood)
				- {{ $entity->getPrimaryLocation()->neighborhood }}
				@endif
			</span>
		</div>
		@endif

		<!-- Roles -->
		@unless ($entity->roles->isEmpty())
		<div class="flex flex-wrap gap-1.5">
			@foreach ($entity->roles as $role)
			<a href="/entities/role/{{ $role->name }}" class="badge-tw badge-primary-tw text-xs hover:bg-primary/30">
				{{ $role->name }}
			</a>
			@endforeach
		</div>
		@endunless

		<!-- Tags -->
		@unless ($entity->tags->isEmpty())
		<div class="flex flex-wrap gap-1.5">
			@foreach ($entity->tags as $tag)
				<x-tag-badge :tag="$tag" context="entities" />
			@endforeach
		</div>
		@endunless

		<!-- Embeds (lazy-loaded) -->
		<div id="card-entity-minimal-playlist">
		@include('embeds.minimal-playlist', ['entity' => $entity])
		</div>

		<!-- Action Icons Footer -->
		<div class="pt-3 border-t border-border flex items-center justify-between">
			<div class="flex items-center gap-3">
				<!-- Related Events -->
				<a href="/events/related-to/{{ urlencode($entity->slug) }}"
					title="View related events"
					class="text-muted-foreground hover:text-primary transition-colors">
					<i class="bi bi-calendar-fill text-lg"></i>
				</a>

				<!-- Edit (if owner) -->
				@if ($signedIn && $entity->ownedBy($user))
				<a href="{!! route('entities.edit', ['entity' => $entity->slug]) !!}"
					title="Edit {{ $entity->name }}"
					class="text-muted-foreground hover:text-primary transition-colors">
					<i class="bi bi-pencil-fill text-lg"></i>
				</a>
				@endif

				<!-- Auto Relate (admin only) -->
				@if (isset($search) && $signedIn && $user->can('show_admin'))
				<a href="{!! route('pages.relate', ['id' => $entity->id, 'keyword' => $search]) !!}"
					title="Auto relate {{ $entity->name }}"
					class="text-muted-foreground hover:text-primary transition-colors">
					<i class="bi bi-link-45deg text-lg"></i>
				</a>
				@endif
			</div>

			<!-- Social Links -->
			<div class="flex items-center gap-2">
				@if ($entity->facebook_username)
				<a href="https://facebook.com/{{ $entity->facebook_username }}"
					target="_blank"
					title="Facebook"
					class="text-muted-foreground hover:text-primary transition-colors">
					<i class="bi bi-facebook"></i>
				</a>
				@endif

				@if ($entity->twitter_username)
				<a href="https://twitter.com/{{ $entity->twitter_username }}"
					target="_blank"
					title="Twitter"
					class="text-muted-foreground hover:text-primary transition-colors">
					<i class="bi bi-twitter"></i>
				</a>
				@endif

				@if ($entity->instagram_username)
				<a href="https://instagram.com/{{ $entity->instagram_username }}"
					target="_blank"
					title="Instagram"
					class="text-muted-foreground hover:text-primary transition-colors">
					<i class="bi bi-instagram"></i>
				</a>
				@endif

				@if ($entity->soundcloudLink !== null)
				<a href="{{ $entity->soundcloudLink->url}}"
					target="_blank"
					title="SoundCloud"
					class="text-muted-foreground hover:text-primary transition-colors">
					<i class="bi bi-music-note-beamed"></i>
				</a>
				@elseif ($entity->bandcampLink !== null)
				<a href="{{ $entity->bandcampLink->url}}"
					target="_blank"
					title="Bandcamp"
					class="text-muted-foreground hover:text-primary transition-colors">
					<i class="bi bi-music-note-beamed"></i>
				</a>
				@endif
			</div>
		</div>


	</div>
</article>
