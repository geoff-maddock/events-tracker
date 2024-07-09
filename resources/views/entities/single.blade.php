<li id="entity-{{ $entity->id }}" class="flow-root event-card @if ($entity->entityStatus->name === " Inactive") mute-card @else card @endif">
	@if ($primary = $entity->getPrimaryPhoto())
	<div class="card-thumb  float-start pe-3"">
			<a href="{{ Storage::disk('external')->url($primary->getStoragePath()) }}"
				data-lightbox="{{ Storage::disk('external')->url($primary->getStoragePath()) }}" 
				data-bs-toggle="tooltip" 
				title="Click to see enlarged image">
				<img src="{{ Storage::disk('external')->url($primary->getStorageThumbnail()) }}" alt="{{ $entity->name}}" class="thumbnail-image">
			</a>
	</div>
	@else
	<div class="card-thumb  float-start pe-3"">
		<img src="/images/entity-placeholder.png" alt="{{ $entity->name}}" class="thumbnail-image">
	</div>
	@endif

	{!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'item-title']) !!}
	@if ($entity->entityStatus->name === "Inactive")
	[Inactive]
	@endif

	@if ($signedIn && $entity->ownedBy($user))
	<a href="{!! route('entities.edit', ['entity' => $entity->slug]) !!}" alt="Edit {{ $entity->name }}" aria-label="Edit {{ $entity->name }}">
		<i class="bi bi-pencil-fill card-actions"></i>
	</a>
	@endif

	@if (isset($keyword) && $signedIn && $user->can('show_admin'))
	<a href="{!! route('pages.relate', ['id' => $entity->id, 'keyword' => $keyword]) !!}" alt="Auto Related {{ $entity->name }}" aria-label="Auto Relate {{ $entity->name }}">
		<i class="bi bi-link-45deg card-actions"></i>
	</a>
	@endif

	@if ($signedIn)
	@if ($follow = $entity->followedBy($user))
	<a href="{!! route('entities.unfollow', ['id' => $entity->id]) !!}" data-target="#entity-{{ $entity->id }}"
		class="ajax-action" title="Click to unfollow">
		<i class="bi bi-check-circle-fill card-actions text-info"></i>
	</a>
	@else
	<a href="{!! route('entities.follow', ['id' => $entity->id]) !!}" data-target="#entity-{{ $entity->id }}"
		class="ajax-action" title="Click to follow">
		<i class="bi bi-plus-circle card-actions icon"></i>
	</a>
	@endif
	@endif


	<a href="/events/related-to/{{ urlencode($entity->slug) }}"	title="Related events." alt="Related events"  aria-label="Related Events">
		<i class="bi bi-calendar-fill card-actions"></i>
	</a>

	@if ($entity->facebook_username)
	<a href="https://facebook.com/{{ $entity->facebook_username }}" target="_">	
			<i class="bi bi-facebook card-actions"></i>
	</a>
	@endif

	@if ($entity->twitter_username)
	<a href="https://twitter.com/{{ $entity->twitter_username }}" target="_">	
			<i class="bi bi-twitter card-actions"></i>
	</a>
	@endif

	@if ($entity->instagram_username)
	<a href="https://instagram.com/{{ $entity->instagram_username }}" target="_">	
			<i class="bi bi-instagram card-actions"></i>
	</a>
	@endif

	@if ($entity->soundcloudLink !== null)
	<a href="{{ $entity->soundcloudLink->url}}" target="_">	
			<i class="bi bi-music-note-beamed card-actions"></i>
	</a>
	@elseif ($entity->bandcampLink !== null)
	<a href="{{ $entity->bandcampLink->url}}" target="_">	
		<i class="bi bi-music-note-beamed card-actions"></i>
	</a>
	@endif

	@include('entities.map')

	@if ($type = $entity->entityType)
	<br><b>{{ $entity->entityType->name }}</b>
	@endif

	@if ($entity->getPrimaryLocationAddress() )
	{{ $entity->getPrimaryLocationAddress() }}
		@if($entity->getPrimaryLocation()->neighborhood) 
			- {{ $entity->getPrimaryLocation()->neighborhood }}
		@endif
	@endif
	<br>
	@foreach ($entity->roles as $role)
	<span class="badge rounded-pill bg-dark"><a href="/entities/role/{{ $role->name }}">{{ $role->name }}</a></span>
	@endforeach
	@foreach ($entity->tags as $tag)
		@include('tags.single_entity_label')
	@endforeach
	<br>
	<ul class="vertical-list">
		@if ($events = $entity->futureEvents()->take(1))
		@foreach ($events as $event)
		<li>Next Event:
			<b>{{ $event->start_at->format('m.d.y') }}</b> {!! link_to_route('events.show', $event->name, [$event->id]) !!}
		</li>
		@php unset($event) @endphp
		@endforeach
		@endif
		@if ($events = $entity->pastEvents()->take(1))
		@foreach ($events as $event)
		<li>Past Event:
			<b>{{ $event->start_at->format('m.d.y') }}</b> {!! link_to_route('events.show', $event->name, [$event->id]) !!}
		</li>
		@php unset($event) @endphp
		@endforeach
		@endif
	</ul>
	@php unset($series) @endphp
	@include('embeds.minimal-playlist', ['entity' => $entity])
</li>
