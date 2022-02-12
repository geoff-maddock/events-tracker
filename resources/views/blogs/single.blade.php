<li class="
@if ($permission->permissionStatus->name === "Inactive") mute-card @else card @endif" style="clear: both;">
	@if ($primary = $permission->getPrimaryPhoto())
	<div class="card-thumb float-start pe-3">
			<img src="/{!! str_replace(' ','%20',$permission->getPrimaryPhoto()->thumbnail) !!}" alt="{{ $permission->name}}"  style="max-width: 100px; ">
	</div>
	@endif

	{!! link_to_route('entities.show', $permission->name, [$permission->id], ['class' => 'item-title']) !!}
	@if ($permission->permissionStatus->name === "Inactive")
	[Inactive]
	@endif

	@if ($signedIn && $permission->ownedBy($user))
	<a href="{!! route('entities.edit', ['id' => $permission->id]) !!}">
		<i class="bi bi-pencil"></i>
	</a>
	@endif 
	
	@if ($signedIn)
		@if ($follow = $permission->followedBy($user))
		<a href="{!! route('entities.unfollow', ['id' => $permission->id]) !!}" title="Click to unfollow"><i class="bi bi-dash-circle-fill"></i></a>
		@else
		<a href="{!! route('entities.follow', ['id' => $permission->id]) !!}" title="Click to follow"><i class="bi bi-plus-circle-fill"></i></a>
		@endif
	@endif 


	@if ($type = $permission->permissionType)
		<br><b>{{ $permission->permissionType->name }}</b>
	@endif

	@if ($permission->getPrimaryLocationAddress() )
		{{ $permission->getPrimaryLocationAddress() }} - {{ $permission->getPrimaryLocation()->neighborhood }} 
	@endif
    <br>
	@foreach ($permission->roles as $role)
		<span class="badge rounded-pill bg-dark"><a href="/entities/role/{{ $role->name }}">{{ $role->name }}</a></span>
	@endforeach
	<br>
	<ul class="list">
	@if ($events = $permission->futureEvents()->take(1))
	@foreach ($events as $event)
		<li>Next Event:
		<b>{{ $event->start_at->format('m.d.y')  }}</b> {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!} </li>
	@endforeach
	@endif
	@if ($events = $permission->pastEvents()->take(3))
	@foreach ($events as $event)
		<li>Past Event:
		<b>{{ $event->start_at->format('m.d.y')  }}</b> {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!} </li>
	@endforeach
	@endif
	</ul>
</li>