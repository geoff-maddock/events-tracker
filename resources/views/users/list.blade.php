@if (isset($users) && $users)

<?php $type = NULL;?>
<ul class='list'>
	@foreach ($users as $user)
		@include('users.single', ['user' => $user])
	@endforeach
</ul>
@else
	<div><small>No users found.</small></div>
@endif

