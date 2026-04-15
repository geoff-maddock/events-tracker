@component('mail::message')

# Entity Outreach Summary

Hi Admin,

The monthly entity outreach run has completed. Here is a summary:

- **{{ $emailedCount }}** reminder email(s) sent to entities with contact emails who have not logged in within the past 2 months.
- **{{ $instagramEntities->count() }}** entities found with Instagram usernames but no contact email — listed below for manual outreach.

---

@if ($instagramEntities->count() > 0)
## Instagram Outreach List

The following entities have an Instagram username but no contact email on file. You can reach out to them directly on Instagram.

### Instagram Usernames

@foreach ($instagramEntities as $entity)
- **[{{ $entity->name }}]({{ $url }}entities/{{ $entity->slug }})** — [{{ '@'.$entity->instagram_username }}](https://www.instagram.com/{{ $entity->instagram_username }})
@endforeach

---

## Template Message for Instagram DMs

Below is a template message you can use when reaching out to these entities on Instagram:

---

*Hey {{ '@' }}[USERNAME]! 👋 We wanted to let you know that **{{ $site }}** has a profile for you at {{ $url }} — a community hub for music and arts events. We'd love for you to claim your page, add your upcoming events, and connect with the local scene. Check it out and feel free to reach out if you have any questions! 🎵*

---

@endif

Thanks,
{{ $site }}
{{ $url }}

@endcomponent
