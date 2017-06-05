<li class="
@if ($group->groupStatus->name === "Inactive") mute-card @else card @endif" style="clear: both;">
	@if ($primary = $group->getPrimaryPhoto())
	<div class="card-thumb" style="float: left; padding: 5px;">
			<img src="/{!! str_replace(' ','%20',$group->getPrimaryPhoto()->thumbnail) !!}" alt="{{ $group->name}}"  style="max-width: 100px; ">
	</div>
	@endif

	{!! link_to_route('groups.show', $group->name, [$group->id], ['class' => 'item-title']) !!}
	@if ($group->groupStatus->name === "Inactive")
	[Inactive]
	@endif

	@if ($signedIn && $group->ownedBy($user))
	<a href="{!! route('groups.edit', ['id' => $group->id]) !!}">
	<span class='glyphicon glyphicon-pencil'></span></a>
	@endif 
	
	@if ($signedIn)
	@if ($follow = $group->followedBy($user))
	<a href="{!! route('groups.unfollow', ['id' => $group->id]) !!}" title="Click to unfollow"><span class='glyphicon glyphicon-minus-sign text-warning'></span></a>
	@else
	<a href="{!! route('groups.follow', ['id' => $group->id]) !!}" title="Click to follow"><span class='glyphicon glyphicon-plus-sign text-info'></span></a>
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
	<span class="label label-tag"><a href="/groups/role/{{ $role->name }}">{{ $role->name }}</a></span>
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