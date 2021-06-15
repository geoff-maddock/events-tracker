@component('mail::message')

Hello Admin User!

This is a test notification sent via AdminTest command which is scheduled in Kernel.php.

It was created at {{ Carbon\Carbon::now()->toDateTimeString() }}

Thanks!<br>
{{ $site }}<br>
{{ $url }}<br>

<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent