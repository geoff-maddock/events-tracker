@if (isset($tag))
	. {{  ucfirst($tag) }}
@else
	. Select a TAG to filter
@endif
