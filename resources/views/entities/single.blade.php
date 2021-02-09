<li id="entity-{{ $entity->id }}" class="@if ($entity->entityStatus->name === " Inactive") mute-card @else card @endif"
	style="display: flow-root;">
	@if ($primary = $entity->getPrimaryPhoto())
	<div class="card-thumb" style="float: left; padding: 5px;">
		<img src="/{!! str_replace(' ','%20',$entity->getPrimaryPhoto()->thumbnail) !!}" alt="{{ $entity->name}}"
			style="max-width: 100px; ">
	</div>
	@else
	<div class="card-thumb" style="float: left; padding: 5px;">
		<img src="/images/entity-placeholder.png" alt="{{ $entity->name}}" style="max-width: 100px; ">
	</div>
	@endif

	{!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'item-title']) !!}
	@if ($entity->entityStatus->name === "Inactive")
	[Inactive]
	@endif

	@if ($signedIn && $entity->ownedBy($user))
	<a href="{!! route('entities.edit', ['entity' => $entity->slug]) !!}"><span
			class='glyphicon glyphicon-pencil'></span></a>
	@endif

	@if ($signedIn)
	@if ($follow = $entity->followedBy($user))
	<a href="{!! route('entities.unfollow', ['id' => $entity->id]) !!}" data-target="#entity-{{ $entity->id }}"
		class="ajax-action" title="Click to unfollow"><span
			class='glyphicon glyphicon-minus-sign text-warning'></span></a>
	@else
	<a href="{!! route('entities.follow', ['id' => $entity->id]) !!}" data-target="#entity-{{ $entity->id }}"
		class="ajax-action" title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
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
	<span class="label label-tag"><a href="/entities/role/{{ $role->name }}">{{ $role->name }}</a></span>
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