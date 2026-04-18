@component('mail::message')

Hello!

This is a friendly update from **{{ $site }}** about your listing for **{{ $entity->name }}**.

We wanted to share a summary of the activity related to your profile on {{ $url }}, along with upcoming and past events.

---

## Your Profile

**Name:** {{ $entity->name }}

@if ($entity->entityType)
**Type:** {{ $entity->entityType->name }}

@endif
@if ($entity->entityStatus)
**Status:** {{ $entity->entityStatus->name }}

@endif
@if ($entity->short)
**Summary:** {{ $entity->short }}

@endif
@if ($entity->description)
**Description:** {{ $entity->description }}

@endif
@if ($entity->getRoleString())
**Roles:** {{ $entity->getRoleString() }}

@endif

[View your profile on {{ $site }}]({{ $url }}entities/{{ $entity->slug }})

---

@if ($upcomingEvents->count() > 0)
## Upcoming Events ({{ $upcomingEvents->count() }})

Here are the upcoming events associated with your listing:

@foreach ($upcomingEvents as $event)
### {{ $event->start_at->format('l, F jS Y') }}
**[{{ $event->name }}]({{ $url }}events/{{ $event->slug }})**
{{ $event->start_at->format('g:i A') }}@if ($event->end_time) – {{ $event->end_time->format('g:i A') }}@endif
@if ($event->venue)
at [{{ $event->venue->name }}]({{ $url }}entities/{{ $event->venue->slug }})
@endif
@if ($event->short)
*{{ $event->short }}*
@endif

---
@endforeach

@else
## Upcoming Events

No upcoming events are currently listed for {{ $entity->name }}. We'd love to see your upcoming events on the site!

---
@endif

@if ($pastEvents->count() > 0)
## Recent Past Events ({{ $pastEvents->count() }})

Here is a summary of recent past events:

@foreach ($pastEvents as $event)
- **{{ $event->start_at->format('M j, Y') }}** – [{{ $event->name }}]({{ $url }}events/{{ $event->slug }})@if ($event->venue) at {{ $event->venue->name }}@endif

@endforeach

---
@endif

@if ($frequentlyPerformsWith->count() > 0)
## Frequently Performs With

Based on your event history, {{ $entity->name }} frequently performs with:

@foreach ($frequentlyPerformsWith as $coEntity)
- [{{ $coEntity->name }}]({{ $url }}entities/{{ $coEntity->slug }})@if (isset($coEntity->frequency)) ({{ $coEntity->frequency }} shared {{ $coEntity->frequency === 1 ? 'event' : 'events' }})@endif

@endforeach

---
@endif

@if ($frequentlyPerformsAt->count() > 0)
## Venues Frequently Performed At

Based on your event history, {{ $entity->name }} frequently performs at:

@foreach ($frequentlyPerformsAt as $venue)
- [{{ $venue->name }}]({{ $url }}entities/{{ $venue->slug }})@if (isset($venue->frequency)) ({{ $venue->frequency }} {{ $venue->frequency === 1 ? 'event' : 'events' }})@endif

@endforeach

---
@endif

## Keep Your Profile Up to Date

We invite you to log in and update your profile on {{ $site }}, add upcoming events, or sign up if you don't already have an account.

@component('mail::button', ['url' => $url . 'login'])
Log In to {{ $site }}
@endcomponent

Not yet registered?

@component('mail::button', ['url' => $url . 'register', 'color' => 'success'])
Create a Free Account
@endcomponent

If you have any questions or feedback, feel free to [reach out to us](mailto:{{ $admin_email }}).

Thanks!
{{ $site }}
{{ $url }}

<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent
