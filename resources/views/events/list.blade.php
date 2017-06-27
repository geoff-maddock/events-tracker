@if (count($events) > 0)

<ul class='event-list'>
	<?php $month = '';?>
	@foreach ($events as $event)

				@include('events.single', ['event' => $event])

	@endforeach
</ul>

@else
	<ul class='event-list'><li style='clear:both;'><i>No events listed</i></li></ul> 
@endif
