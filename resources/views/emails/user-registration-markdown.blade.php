@component('mail::message')

Hello!  

A new user has registered an account on {{ $site }}.  

Username: **[{{ $user->name }}]({{ $url }}users/{{$user->id }})**  
Full Name: **{{ $user->full_name }}**  

You can *activate* the user by clicking [here]({{ $url }}users/{{$user->id }}/activate).  
You can *suspend* the user by clicking [here]({{ $url }}users/{{$user->id }}/suspend).  
You can *delete* the user by clicking [here]({{ $url }}users/{{$user->id }}/delete).  

Thanks!  
{{ $site }}  
{{ $url }}  

<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent