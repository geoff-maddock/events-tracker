@if (isset($tag))
• {{ ucfirst($tag->name) }}
@endif
@if (isset($related))
• {{ ucfirst($related->name) }}
@endif
@if (isset($type))
• {{ ucfirst($type) }}
@endif
@if (isset($slug))
• {{ ucfirst($slug) }}
@endif
@if (isset($cdate))
• {{ $cdate->toDateString() }}
@endif