Hello!  

A new user has registered an account on {{ $site }}.  

Username: {{ $user->name }}
Full Name: {{ $user->full_name }}

You can *activate* the user by clicking ({{ $url }}users/{{$user->id }}/activate.  
You can *suspend* the user by clicking {{ $url }}users/{{$user->id }}/suspend.  
You can *delete* the user by clicking {{ $url }}users/{{$user->id }}/delete.  

Thanks!  
{{ $site }}  
{{ $url }}  
