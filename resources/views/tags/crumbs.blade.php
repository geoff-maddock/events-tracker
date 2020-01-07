@if (isset($tag))
	. {{  ucfirst($tag) }}
@else
	. click a <b>keyword</b> tag in the list to filter.
@endif
