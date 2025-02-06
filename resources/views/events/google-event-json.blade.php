<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Event",
    "name": "{{ $event->name}}",
    "startDate": "{!! $event->start_at->format(DateTimeInterface::ISO8601) !!}",
    @if ($event->default_end_time !== null)
    "endDate": "{!! $event->default_end_time->format(DateTimeInterface::ISO8601) !!}",
    @endif
    "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
    "eventStatus": "https://schema.org/EventScheduled",
    @if ($photo = $event->getPrimaryPhoto())
    @php
        $image = Storage::disk('external')->url($photo->getStoragePath());
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
        @if ($event->ticket_link !== null)
        "url": "{{ $event->ticket_link}}",
        @elseif ($event->primary_link !== null)
        "url": "{{ $event->primary_link}}",
        @else
        "url": "{!! URL::route('events.show', $event->slug) !!}",
        @endif
        "price": "{{ $event->door_price ? $event->door_price : 0}}",
        "priceCurrency": "USD",
        "availability": "https://schema.org/InStock"
        @if ($event->created_at !== null)
        ,"validFrom": "{{ $event->created_at->format(DateTimeInterface::ISO8601) }}"
        @endif
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
        @if ($performer->primaryLink() !== null)
        ,"url": "{{ $performer->primaryLink()->url}}"
        @endif
    }@if (!$loop->last), @endif
    @endforeach
    ]
    @else
    ,"performer": [
        {
            "@type": "PerformingGroup",
            "name": "{{ $event->name }}"
            @if ($event->primary_link !== null)
            ,"url": "{{ $event->primary_link }}"
            @endif
        }
        ]
    @endif
    @if (!empty($event->promoter_id))
    ,"organizer": {
        "@type": "Organization",
        "name": "{{ $event->promoter->name}}"
        @if ($event->promoter->primaryLink() !== null)
        ,"url": "{{ $event->promoter->primaryLink()->url}}"
        @endif
    }
    @else		
    @if (!empty($event->venue_id))
    ,"organizer": {
        "@type": "Organization",
        "name": "{{ $event->venue->name}}"
        @if ($event->venue->primaryLink() !== null)
        ,"url": "{{ $event->venue->primaryLink()->url}}"
        @endif
    }	
    @endif
    @endif
}
</script>
