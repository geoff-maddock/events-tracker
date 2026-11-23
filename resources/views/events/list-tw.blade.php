@if (count($events) > 0)

<ul class='event-list space-y-4'>
	@foreach ($events as $event)
		@include('events.single-tw', ['event' => $event])
	@endforeach
</ul>

@else
	<x-empty-state compact>No events listed today.</x-empty-state>
@endif
