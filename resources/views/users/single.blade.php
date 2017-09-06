<li class="card" style="clear: both;">
	@if ($primary = $user->getPrimaryPhoto())
	<div class="card-thumb" style="float: left; padding: 5px;">
			<img src="/{!! str_replace(' ','%20',$user->getPrimaryPhoto()->thumbnail) !!}" alt="{{ $user->name}}"  style="max-width: 100px; ">
	</div>
	@else
		<div class="card-thumb" style="float: left; padding: 5px;">
			<img src="/images/avatar-placeholder-generic.jpg"  style="max-width: 100px; ">
		</div>
	@endif

	{!! link_to_route('users.show', $user->name, [$user->id]) !!}


	@if ($signedIn && (Auth::user()->id == $user->id || Auth::user()->id == Config::get('app.superuser') ))
	<a href="{!! route('users.edit', ['id' => $user->id]) !!}">
	<span class='glyphicon glyphicon-pencil'></span></a>
    {!! link_form_icon('glyphicon-trash text-warning', $user, 'DELETE', 'Delete the user') !!}
	@endif

	<ul class="list">
	@if ($events = $user->events->take(3))
	@foreach ($events as $event)
		<li>Events:
		<b>{{ $event->start_at->format('m.d.y')  }}</b> {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!} </li>
	@endforeach
	@endif

	</ul>
</li>
