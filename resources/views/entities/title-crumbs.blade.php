@if (isset($tag))
• {{ ucfirst($tag)}}
@endif
@if (isset($role))
• {{ ucfirst($role) }}
@endif
@if (isset($type))
• {{ ucfirst($type) }}
@endif
@if (isset($slug))
• {{ strtoupper($slug) }}
@endif 