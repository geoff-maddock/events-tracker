<ul class='event-list'>

    @php $type = NULL @endphp
	@if (count($series) > 0)
	@foreach ($series as $s)
		@if ($type !== $s->occurrence_type_id)
			<li style="margin-left: 10px;">
				<h3>{{ $s->occurrenceType->name }}</h3>
                <?php $type = $s->occurrence_type_id; ?>
			</li>
		@endif
		@include('series.single', ['series' => $s])
	@endforeach
	@else
		<ul class='event-list'><li style='clear:both;'><i>No series listed</i></li></ul>
	@endif

</ul>

<br>
