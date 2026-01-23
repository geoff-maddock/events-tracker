@if (count($events) > 0)

<ul class='space-y-3'>
	@foreach ($events as $event)
		@include('events.single-tw', ['event' => $event])
	@endforeach
</ul>

@else
	<div class="text-center py-4 text-muted-foreground">
		<small>No events listed today.</small>
	</div>
@endif
