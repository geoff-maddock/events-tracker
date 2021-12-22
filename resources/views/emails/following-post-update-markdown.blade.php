@component('mail::message')

Good morning!

There was a new post in a thread that you are following with the subject **{!! $thread->name !!}**.

@unless ($tag == null)
You received this notificaiton because you are following the keyword tag **{{ $tag->name }}**
@endunless

@include('emails.post-update-markdown')
 
Thanks!  
{{ $site }}  
{{ $url }}  

<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent
