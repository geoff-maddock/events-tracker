<li class="
@if ($group->groupStatus->name === "Inactive") mute-card @else card @endif flow-root">
	@if ($primary = $group->getPrimaryPhoto())
	<div class="card-thumb float-start pe-3">
		<img src="/{!! str_replace(' ','%20', $group->getPrimaryPhoto()->thumbnail) !!}" alt="{{ $group->name}}" class="thumbnail-image">
	</div>
	@endif

	{!! link_to_route('groups.show', $group->name, [$group->id], ['class' => 'item-title']) !!}
	@if ($group->groupStatus->name === "Inactive")
	[Inactive]
	@endif

	@if ($signedIn && $group->ownedBy($user))
	<a href="{!! route('groups.edit', ['id' => $group->id]) !!}">
		<i class="bi bi-pencil-fill icon"></i>
	</a>
	@endif 
	
	@if ($signedIn)
	@if ($follow = $group->followedBy($user))
	<a href="{!! route('groups.unfollow', ['id' => $group->id]) !!}" title="Click to unfollow"><i class="bi bi-dash-circle-fill"></i></a>
	@else
	<a href="{!! route('groups.follow', ['id' => $group->id]) !!}" title="Click to follow"><i class="bi bi-plus-circle-fill"></i></a>
	@endif

	@endif 


	@if ($type = $group->groupType)
		<br><b>{{ $group->groupType->name }}</b>
	@endif

	@if ($group->getPrimaryLocationAddress() )
		{{ $group->getPrimaryLocationAddress() }} - {{ $group->getPrimaryLocation()->neighborhood }} 
	@endif
    <br>
	@foreach ($group->roles as $role)
		<span class="badge rounded-pill bg-dark"><a href="/groups/role/{{ $role->name }}">{{ $role->name }}</a></span>
	@endforeach
	<br>
	<ul class="list">
	@if ($events = $group->futureEvents()->take(1))
	@foreach ($events as $event)
		<li>Next Event:
		<b>{{ $event->start_at->format('m.d.y')  }}</b> {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!} </li>
	@endforeach
	@endif
	@if ($events = $group->pastEvents()->take(3))
	@foreach ($events as $event)
		<li>Past Event:
		<b>{{ $event->start_at->format('m.d.y')  }}</b> {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!} </li>
	@endforeach
	@endif
	</ul>
</li>