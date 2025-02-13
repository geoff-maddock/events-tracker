<li class="card event-card flow-root">
	@if ($primary = $user->getPrimaryPhoto())
	<div class="event-list-thumbnail">
			<img src="{!! str_replace(' ','%20', Storage::disk('external')->url($primary->getStoragePath())) !!}" alt="{{ $user->name}}"  class="thumbnail-image">
	</div>
	@else
	<div class="event-list-thumbnail">
			<img src="/images/avatar-placeholder-generic.jpg"  class="thumbnail-image">
		</div>
	@endif
	<span>
	{!! link_to_route('users.show', $user->name, [$user->id]) !!}
	</span>
	@if ($signedIn && (Auth::user()->id === $user->id || Auth::user()->id === Config::get('app.superuser') || Auth::user()->hasGroup('super_admin') ))

		<a href="{!! route('users.edit', ['user' => $user->id]) !!}"><i class="bi bi-pencil-fill card-actions"></i></a>

		<span class="card-actions">
    		{!! link_form_bootstrap_icon('bi bi-trash-fill text-warning card-actions py-0 my-0 icon', $user, 'DELETE', 'Delete the user', NULL, 'py-0 my-0 delete') !!}
		</span>

		@can('grant_access')
			@if (!$user->isActive)
			<a href="{!! route('users.activate', ['id' => $user->id]) !!}" class="confirm">
				<i class="bi bi-check-circle card-actions" title='Activate the user'></i>
			</a>
			@endif
		@endcan
		@can('grant_access')
			@if ($user->isActive)
				<a href="{!! route('users.reminder', ['id' => $user->id]) !!}"  class="confirm">
					<i class="bi bi-pin-fill card-actions"  title='Send reminder'></i>
				</a>
			@endif
		@endcan
		@can('impersonate_user')
			<a href="{!! route('user.impersonate', ['user' => $user->id]) !!}" title="Impersonate User"  class="confirm">
				<i class="bi bi-person-fill card-actions"></i>
			</a>
		@endif
		@can('grant_access')
			@if ($user->isActive)
				<a href="{!! route('users.weekly', ['id' => $user->id]) !!}"  class="confirm">
					<i class="bi bi-envelope-fill card-actions" title='Send weekly update'></i>
				</a>
			@endif
		@endcan
	@endif

	<ul class="list d-inline">
        <small><br>

            <b>Joined:</b> {{ $user->created_at->format('m.d.y') }}<br>
			Logged in {{ $user->loginCount }} times<br>
			<b>Last Active:</b> {{ $user->lastActivity ?  $user->lastActivity->created_at->format('m.d.y') : 'Never'}}<br>



        @if ($events = $user->getAttending()->get()->take(3) and count($events) > 0)
            <br>
            <b>Events Attending [{!! link_to_route('users.attending', 'All', [$user->id]) !!}]</b>
            @foreach ($events as $event)
                <li><b>{{ $event->start_at->format('m.d.y')  }}</b> {!! link_to_route('events.show', $event->name, [$event->id], ['class' =>'butt']) !!} </li>
            @endforeach
	    @endif
        </small>
	</ul>
</li>
