@if (count($events) > 0)

<ul class='event-list'>
	<?php $month = '';?>
	@foreach ($events as $event)
				@include('events.single', ['event' => $event])
	@endforeach
</ul>

@else
	<div><small>No events listed today.</small></div>
@endif
