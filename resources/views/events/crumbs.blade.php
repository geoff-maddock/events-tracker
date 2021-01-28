@if (isset($tag))
. {{ ucfirst($tag) }}
@endif
@if (isset($type))
. {{ ucfirst($type) }}
@endif
@if (isset($slug))
. {{ ucfirst($slug) }}
@endif
@if (isset($cdate))
. {{ $cdate->toDateString() }}
@endif