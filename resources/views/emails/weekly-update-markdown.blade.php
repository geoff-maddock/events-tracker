@component('mail::message')

Good morning!

This is your weekly update on upcoming events related to artists, venues, promoters, and keywords you follow on {{ $url }}.

@if (count($events) > 0)
### Summary of events:
@foreach ($events as $event)
1. [{{$event->name}}]({{ $url }}events/{{$event->id }})
@endforeach

***

@foreach ($events as $event)
@include('emails.event-update-markdown')
@endforeach

@endif

@if (count($seriesList) > 0)
Here are the event series you follow that are forthcoming.

### Summary of series:
@foreach ($seriesList as $series)
1. [{{$series->name}}]({{ $url }}series/{{$series->id }})
@endforeach

@foreach ($seriesList as $series)
@include('emails.series-markdown')
@endforeach
@endif

@if (count($events) == 0 && count($seriesList) == 0)
You have no events specifically happening this week.
@endif

@if (count($interests) > 0)
Here are some upcoming events that you might be interested in based on the entities and keywords you are following.

### Summary of events:
@foreach ($interests as $tag => $list)
@foreach ($list as $event)
1. [{{$event->name}}]({{ $url }}events/{{$event->id }})
@endforeach
@endforeach

@foreach ($interests as $tag => $list)

# {{ $tag }}
@if (count($list) == 0) *None listed*  @endif

@foreach ($list as $event)
@include('emails.event-update-markdown')
@endforeach
@endforeach
@endif

We're constantly adding new features, functionality and updates to improve your experience, so check back regularly!

If you have any feedback, don't hesitate to [drop us a line](mailto:{{ $admin_email}}).

Thanks!  
{{ $site }}  
{{ $url }}  

<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent
