@component('mail::message')    
Hello {{ $user->name }},  

We're writing to inform you that there have been {{ $fails }} login failures on your account in the past 30 minutes.  

If this was you, either verify your login information or reset your password.

Visit the site at [{{ $url }}]({{ $url }}).  

If this was not you, contact our administrators, at (mailto:{{ $admin_email}}).
    
Thanks!  
{{ $site }}  
{{ $url }}  
    
<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent