@component('mail::message', ['unsubscribeUrl' => $unsubscribeUrl ?? null])

Good morning!

@if ($tag)
You might be interested in this new thread because you are following  **{{ $tag->name }}**.
@elseif ($series ?? null)
You might be interested in this new thread because you are following the series **{{ $series->name }}**.
@else
You might be interested in this new thread.
@endif

@include('emails.thread-update-markdown')

Thanks!  
{{ $site }}  
{{ $url }}  

<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent
