<html>
<body>
<div>
	A new user has registered an account on {{ $site }}.
</div>
<P></P>
<div>
	Username: <b>{{ $user->name }}</b><br>
	Full Name: <b>{{ $user->full_name }}</b><br>
</div>
<P></P>
<div>
	You can <i>activate</i> the user by clicking <a href="{{ $url }}/users/{{$user->id }}/activate">here</a>.<br>
	You can <i>suspend</i> the user by clicking <a href="{{ $url }}/users/{{$user->id }}/suspend">here</a>.<br>
	You can <i>delete</i> the user by clicking <a href="{{ $url }}/users/{{$user->id }}/delete">here</a>.<br>
</div>
<P></P>
Sincerly,<br>
{{ $site }}
{{ $url }}
</body>
</html>