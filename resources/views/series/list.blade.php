<ul class='event-list'>

<?php $type = NULL;?>
	@foreach ($series as $series)
		@if ($type != $series->occurrence_type_id)
			<li style="margin-left: 10px;">			<br style="clear: left;"/>
				<h3>{{ $series->occurrenceType->name }}</h3>
                <?php $type = $series->occurrence_type_id; ?>
			</li>
		@endif
		@include('series.single', ['series' => $series])
	@endforeach
 </ul>

<br>