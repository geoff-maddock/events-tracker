@component('mail::message')

# Essential Events

Good morning! Here are our picks for the essential events coming up in {{ $site }}'s corner of the world.

### Summary of events:
@foreach ($events as $event)
1. [{{ $event->name }}@if ($event->cancelled_at) **(CANCELLED)**@endif]({{ $url }}events/{{ $event->slug }})@if ($event->essential_note) — {{ $event->essential_note }}@endif
@endforeach

***

@foreach ($events as $event)
@include('emails.event-update-markdown')
@endforeach

Want updates tailored to the artists, venues and genres *you* follow? [Create a free account]({{ $url }}register?email={{ urlencode($subscriber->email) }}) to follow what you love and get a personalized weekly update.

Thanks!
{{ $site }}
{{ $url }}

You're receiving this because you subscribed to the Essential Events digest. [Unsubscribe]({{ $unsubscribeUrl }}) at any time.
@endcomponent
