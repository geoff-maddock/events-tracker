@if (count($events) > 0)

<ul class='event-list space-y-4'>
	@foreach ($events as $event)
		@include('events.single-tw', ['event' => $event])
	@endforeach
</ul>

@else
	<div class="text-center py-8 text-muted-foreground">
		<small>No events listed today.</small>
	</div>
@endif
