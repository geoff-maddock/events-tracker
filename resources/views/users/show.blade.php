@extends('app')

@section('content')

	<h2>{{ $user->name }}</h2>
	<h5>Added {{ $user->created_at }}</h5>
	<h3>Attending {{ $user->attendingCount }}</h3>
	<br>
	{{ $user->getEmail }}

	<p>
	<ul class='event-list'>
	<?php $month = '';?>
	@foreach ($user->events as $event)
	<li>
		@if ($month != $event->start_at->format('F'))
		<h4>{!! $event->start_at->format('F') !!}</h4>
		<?php $month = $event->start_at->format('F')?>
		@endif
		<div class='event-date'>{!! $event->start_at->format('l F jS Y') !!}</div>
		{!! link_to_route('events.show', $event->name, [$event->id]) !!} 

			@if ($signedIn && $event->ownedBy($user))
			<a href="{!! route('events.edit', ['id' => $event->id]) !!}">
			<span class='glyphicon glyphicon-pencil'></span>
			</a>
			@endif

		<br>
		{{ $event->eventType->name or ''}} at {{ $event->venue->name or 'No venue specified' }}
	</li>
	@endforeach
	</ul>	

	{!! link_to_route('users.index','Return to list') !!}
@stop
