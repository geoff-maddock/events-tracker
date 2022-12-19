@if (isset($user))
. {{ ucfirst($user->name) }}
@endif
@if (isset($slug))
. {{ ucfirst($slug) }}
@endif
