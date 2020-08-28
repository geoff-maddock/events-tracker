@if (isset($entities) && count($entities) > 0)

<?php $type = NULL;?>
<ul class='list'>
	@foreach ($entities as $entity)

	<li id="entity-{{ $entity->id }}" class="@if ($entity->entityStatus && $entity->entityStatus->name === "Inactive") mute-card @else card @endif" style="clear: both;">
		@if ($primary = $entity->getPrimaryPhoto())
		<div class="card-thumb" style="float: left; padding: 5px;">
				<img src="/{!! str_replace(' ','%20',$entity->getPrimaryPhoto()->thumbnail) !!}" alt="{{ $entity->name}}"  style="max-width: 100px; ">
		</div>
		@endif

		{!! link_to_route('entities.show', $entity->name, [$entity->slug], ['class' => 'item-title']) !!}
		@if ($entity->entityStatus && $entity->entityStatus->name === "Inactive")
		 <span class='glyphicon glyphicon-warning-sign text-warning' title="Inactive"></span>
		@endif

		@if ($signedIn && $entity->ownedBy($user))
		<a href="{!! route('entities.edit', ['entity' => $entity->slug]) !!}" title="Click to edit"><span class='glyphicon glyphicon-pencil'></span></a>
		@endif

		@if ($signedIn)
			@if ($follow = $entity->followedBy($user))
			<a href="{!! route('entities.unfollow', ['id' => $entity->id]) !!}" data-target="#entity-{{ $entity->id }}" class="ajax-action" title="Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
			@else
			<a href="{!! route('entities.follow', ['id' => $entity->id]) !!}" data-target="#entity-{{ $entity->id }}" class="ajax-action" title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
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
			@if ($entity->tags)
			@foreach ($entity->tags as $tag)
					<span class="label label-tag"><a href="/entities/tag/{{ urlencode($tag->name) }}" class="label-link">{{ $tag->name }}</a>
                        <a href="{!! route('tags.show', ['tag' => $tag->name]) !!}" title="Show this tag."><span class='glyphicon glyphicon-link text-info'></span></a>
                    </span>
			@endforeach
			@endif
		<br>
		<ul class="list" style="">
		@if ($events = $entity->futureEvents()->take(1))
		@foreach ($events as $event)
            <li>
                <b>Next Event</b>
				@if ($primary = $event->getPrimaryPhoto())
					<div class="week-text" style="float: left; padding: 5px;">
						<a href="/{{ $event->getPrimaryPhoto()->path }}" data-lightbox="{{ $event->getPrimaryPhoto()->path }}"><img src="/{{ $event->getPrimaryPhoto()->thumbnail }}" alt="{{ $event->name}}" ></a>
					</div>
				@endif
			<b>{{ $event->start_at->format('m.d.y')  }}</b> {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!}
            </li>
		@endforeach
		@endif
			@if ($entity->hasRole('venue'))
            <a href="events/filter?filter_venue={{ $entity->name }}" title="Show events at this venue.">...</a>
			@else
				<a href="{!! route('events.relatedto', ['slug' => $entity->slug]) !!}" title="Show related event.">...</a>
			@endif

		</ul>
	</li>
	@endforeach
</ul>
@else
	<ul class='event-list'><li style='clear:both;'><i>No entities listed</i></li></ul>
@endif

