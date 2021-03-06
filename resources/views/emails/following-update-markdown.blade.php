@component('mail::message')

Good morning!

You might be interested in this new event because you are following **{!! $tag->name !!}**.

@include('emails.event-update-markdown')

Thanks!  
{{ $site }}  
{{ $url }}  

<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent
