@component('mail::message')    
Hello {{ $user->name }},  

We're writing to inform you that your account was **approved** and fully activated!  

You can now make additions to the site by posting events, entities, series or on the forum.  

Visit the site at [{{ $url }}]({{ $url }}).  

We're constantly adding new features, functionality and updates to improve your experience.  

If you have any feedback, don't hesitate to [drop us a line](mailto:{{ $admin_email}}).
    
Thanks!  
{{ $site }}  
{{ $url }}  
    
<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent