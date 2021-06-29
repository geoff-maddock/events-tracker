@component('mail::message')    
Hello {{ $user->name }},  

We're writing to inform you that your account was **suspended**.

Please review the site's [Terms of Service]({{ $url }}users/{{$user->id }}/tos) reguarding permitted and prohibitted uses.

If you have any questions and would like to have your account re-activated, please [contact the site admin directly](mailto:{{ $admin_email}}).
    
Thanks!  
{{ $site }}  
{{ $url }}  
    
<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent