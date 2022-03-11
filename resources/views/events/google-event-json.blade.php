<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Event",
    "name": "{{ $event->name}}",
    "startDate": "{!! $event->start_at->format(DateTimeInterface::ISO8601) !!}",
@if (isset($event->end_at))
    "endDate": "{!! $event->end_at->format(DateTimeInterface::ISO8601) !!}",
@endif
    "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
    "eventStatus": "https://schema.org/EventScheduled",
    @if ($photo = $event->getPrimaryPhoto())
    @php
        $image = substr(config('app.url'),0,-1).$photo->getStoragePath();
    @endphp
    "image": [
        "{{ $image }}"
    ],
    @endif
    @if (!empty($event->venue_id))
    "location": {
        "@type": "Place",
        "name": "{!! $event->venue->name !!}"
        @if ($event->venue->getPrimaryLocationAddress(true))
        ,
        @php
            $location = $event->venue->locations()->first();
        @endphp
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "{{ $location->address_one }}",
            "addressLocality": "{{ $location->city}}",
            "postalCode": "{{ $location->postcode }}",
            "addressRegion": "{{ $location->state }}",
            "addressCountry": "{{ $location->country}}"
          }
          @endif
      },
    @endif
    "description": "{{ $event->description }}",
    "offers": {
        "@type": "Offer",
        "url": "{{ $event->ticket_link}}",
        "price": "{{ $event->door_price}}",
        "priceCurrency": "USD",
        "availability": "https://schema.org/InStock"
    }
    @php
        $performers = $event->performerEntities(10);
    @endphp
    @if (count($performers) > 0)
    ,"performer": [
    @foreach ($performers as $performer)
    {
        "@type": "PerformingGroup",
        "name": "{{ $performer->name }}"
    }@if (!$loop->last), @endif
    @endforeach
    ]
    @endif
    @if (!empty($event->promoter_id))
    ,"organizer": {
        "@type": "Organization",
        "name": "{{ $event->promoter->name}}"
    }			
    @endif
}
</script>