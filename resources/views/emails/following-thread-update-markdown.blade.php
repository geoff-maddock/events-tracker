@component('mail::message')

Good morning!

You might be interested in this new thread because you are following  **{!! $tag->name !!}**.

@include('emails.thread-update-markdown')

Thanks!  
{{ $site }}  
{{ $url }}  

<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent
