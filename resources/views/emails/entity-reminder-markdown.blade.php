@component('mail::message')

Hi there, {{ $entity->name }}!

We hope this message finds you well. We wanted to reach out to let you know about **{{ $site }}** — a community hub for music and arts events where you already have a presence.

We'd love for you to be more involved! You can log in, add your upcoming events, keep your profile fresh, and connect with the community.

@if ($upcomingEvents->count() > 0)
### Your Upcoming Events

Here are the upcoming events we have listed for you:

@foreach ($upcomingEvents as $event)
- [{{ $event->name }}]({{ $url }}events/{{ $event->slug }}) — {{ $event->start_at->format('l F jS Y \a\t g:i A') }}@if ($event->venue_id) at {{ $event->venue->name }}@endif
@endforeach

@else
We don't have any upcoming events listed for you yet — this is a great opportunity to log in and add some!

@endif

@if ($relatedEntities->count() > 0)
### Artists &amp; Acts You Frequently Perform With

Here are some acts you've shared the stage with:

@foreach ($relatedEntities as $related)
- [{{ $related->name }}]({{ $url }}entities/{{ $related->slug }})
@endforeach

@endif

@if ($frequentVenues->count() > 0)
### Venues You Frequently Perform At

Here are some venues you've performed at:

@foreach ($frequentVenues as $venue)
- [{{ $venue->name }}]({{ $url }}entities/{{ $venue->slug }})
@endforeach

@endif

---

### Here's What You Can Do

@component('mail::button', ['url' => $url . 'login'])
Log In Now
@endcomponent

Once you're logged in, you can:

- **Add your upcoming events** so fans and community members know where to find you
- **Update your profile** with the latest info, photos, and links
- **Browse other events** happening in your community
- **Share the site** with your network to help grow the scene

If you don't have an account yet, you can register at [{{ $url }}register]({{ $url }}register) — it's free!

---

We're building this site for artists, venues, and promoters like you, and your participation makes it better for everyone. We'd love to see you here.

If you have any questions or feedback, just reply to this email — we'd love to hear from you!

Thanks for being part of the community,
{{ $site }}
{{ $url }}

<img src="{{ asset('images/arcane-city-pgh.png') }}">
@endcomponent
