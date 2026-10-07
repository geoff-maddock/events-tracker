@component('mail::message', ['unsubscribeUrl' => $unsubscribeUrl ?? null])

Good morning!

Nothing you follow on {{ $site }} has events this week, so here are the week's most popular events.

### Essential events this week:
@foreach ($essentials->events as $event)
1. [{{ $event->name }}]({{ $url }}events/{{ $event->slug }})
@endforeach

***

@foreach ($essentials->events as $event)
@include('emails.event-update-markdown')
@endforeach

## Make this email yours

Follow artists, venues, promoters and tags, and your weekly update will list what's coming up from them instead.

@if ($essentials->tags->isNotEmpty())
**Popular tags:** @foreach ($essentials->tags as $tag)[{{ $tag->name }}]({{ $url }}tags/{{ $tag->slug }}){{ $loop->last ? '' : ' · ' }}@endforeach

@endif
@if ($essentials->entities->isNotEmpty())
**Popular on {{ $site }}:** @foreach ($essentials->entities as $entity)[{{ $entity->name }}]({{ $url }}entities/{{ $entity->slug }}){{ $loop->last ? '' : ' · ' }}@endforeach

@endif
@component('mail::button', ['url' => $url.'onboarding'])
Pick what to follow
@endcomponent

If you have any feedback, don't hesitate to [drop us a line](mailto:{{ $admin_email }}).

Thanks!  
{{ $site }}  
{{ $url }}  

<img src="{{ asset('images/arcane-city-pgh.png') }}">
@endcomponent
