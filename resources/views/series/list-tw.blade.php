@if (count($series) > 0)
<ul class='event-list space-y-4'>

    @php $type = NULL @endphp

	@foreach ($series as $s)
		@if ($type !== $s->occurrence_type_id)
			<li class="pt-6 first:pt-0">
				<h2 class="text-xl font-bold text-foreground mb-4">{{ $s->occurrenceType->name }}</h2>
                <?php $type = $s->occurrence_type_id; ?>
			</li>
		@endif
		@include('series.single-tw', ['series' => $s])
	@endforeach
</ul>
@else
<div class="text-center py-8 text-muted-foreground">
	<small>No series listed today.</small>
</div>
@endif
