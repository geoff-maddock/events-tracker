@if ($photo = $user->getPrimaryPhoto())
<img src="{!! str_replace(' ','%20', Storage::disk('external')->url($photo->getStoragePath()) ) !!}" alt="{{ $user->name}}" class="avatar" title="{{ $user->name }}">
@else
<img src="/images/avatar-placeholder-generic.jpg" alt="{{ $user->name}}" class="avatar" title="{{ $user->name }}">
@endif
