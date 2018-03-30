@if (isset($tag))
	. {{ ucfirst($tag) }}
@endif
@if (isset($type))
	. {{ ucfirst($type) }}
@endif
@if (isset($cdate))
	. {{ $cdate->toDateString() }}
@endif