@extends('email')
@section('content')

<div class="bg-light m-auto w-75 p-2">
Hello Admin User!
<br>
<br>
This is a test notification sent via AdminTest command which is scheduled in Kernel.php.
<br>
<br>
It was created at {{ Carbon\Carbon::now()->toDateTimeString() }}
<br>
<br>
Thanks!
<br>
{{ $url }}
<br>
<br>
<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@stop