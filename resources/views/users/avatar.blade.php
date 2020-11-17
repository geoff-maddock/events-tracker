@if ($photo = $user->getPrimaryPhoto())
<img src="{!! str_replace(' ','%20', $photo->getStorageThumbnail()) !!}" alt="{{ $user->name}}"  class="avatar" title="{{ $user->name }}">
@else
<img src="/images/avatar-placeholder-generic.jpg" alt="{{ $user->name}}" class="avatar" title="{{ $user->name }}">
@endif
