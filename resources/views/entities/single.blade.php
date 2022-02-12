<li id="entity-{{ $entity->id }}" class="flow-root event-card @if ($entity->entityStatus->name === " Inactive") mute-card @else card @endif">
	@if ($primary = $entity->getPrimaryPhoto())
	<div class="card-thumb  float-start pe-3"">
			<a href="{{ $primary->getStoragePath() }}" data-lightbox="{{ $primary->getStoragePath() }}" data-bs-toggle="tooltip" title="Click to see enlarged image">
				<img src="{{ $primary->getStorageThumbnail() }}" alt="{{ $entity->name}}" class="thumbnail-image">
			</a>
	</div>
	@else
	<div class="card-thumb  float-start pe-3"">
		<img src="/images/entity-placeholder.png" alt="{{ $entity->name}}" style="max-width: 100px; ">
	</div>
	@endif

	{!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'item-title']) !!}
	@if ($entity->entityStatus->name === "Inactive")
	[Inactive]
	@endif

	@if ($signedIn && $entity->ownedBy($user))
	<a href="{!! route('entities.edit', ['entity' => $entity->slug]) !!}">
		<i class="bi bi-pencil-fill card-actions"></i>
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


	@if ($type = $entity->entityType)
	<br><b>{{ $entity->entityType->name }}</b>
	@endif

	@if ($entity->getPrimaryLocationAddress() )
	{{ $entity->getPrimaryLocationAddress() }} - {{ $entity->getPrimaryLocation()->neighborhood }}
	@endif
	<br>
	@foreach ($entity->roles as $role)
	<span class="badge rounded-pill bg-dark"><a href="/entities/role/{{ $role->name }}">{{ $role->name }}</a></span>
	@endforeach
	@foreach ($entity->tags as $tag)
		@include('tags.single_entity_label')
	@endforeach
	<br>
	<ul class="list">
		@if ($events = $entity->futureEvents()->take(1))
		@foreach ($events as $event)
		<li>Next Event:
			<b>{{ $event->start_at->format('m.d.y') }}</b> {!! link_to_route('events.show', $event->name, [$event->id],
			['class' =>'butt']) !!}
		</li>
		@endforeach
		@endif
		@if ($events = $entity->pastEvents()->take(1))
		@foreach ($events as $event)
		<li>Past Event:
			<b>{{ $event->start_at->format('m.d.y') }}</b> {!! link_to_route('events.show', $event->name, [$event->id],
			['class' =>'butt']) !!}
		</li>
		@endforeach
		@endif
	</ul>
</li>