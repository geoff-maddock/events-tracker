@component('mail::message')

Good morning!

@if (count($events) > 0)
Here are the events you are attending today.

### Summary of events:
@foreach ($events as $event)
1. [{{$event->name}}]({{ $url }}events/{{$event->id }})
@endforeach

***

@foreach ($events as $event)
@include('emails.event-markdown')
@endforeach
@endif

@if (count($seriesList) > 0)
Here are the event series you follow that happen today.

### Summary of series:
@foreach ($seriesList as $series)
1. [{{$series->name}}]({{ $url }}series/{{$series->id }})
@endforeach

@foreach ($seriesList as $series)
@include('emails.series-markdown')
@endforeach
@endif

@if (count($interests) > 0)
Here are some events happening today that you might be interested in.  

@foreach ($interests as $tag => $list)

# {{ $tag }}
@if (count($list) == 0) *None listed*  @endif

@foreach ($list as $event)
@include('emails.event-markdown')
@endforeach
@endforeach
@endif

We're constantly adding new features, functionality and updates to improve your experience.  

If you have any feedback, don't hesitate to [drop us a line](mailto:{{ $admin_email}}).

Thanks!  
{{ $site }}  
{{ $url }}  

<img src="{{ asset('images/arcane-city-icon-96x96.png') }}">
@endcomponent