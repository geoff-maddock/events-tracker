@php
    $jsonLd           = $entity->getJsonLd();
    $breadcrumbJsonLd = $entity->getBreadcrumbJsonLd();

    // Add upcoming events array — powers the Google event carousel on venue/artist pages
    if (!empty($relatedEvents) && count($relatedEvents) > 0) {
        $eventItems = [];
        foreach ($relatedEvents as $ev) {
            $eventItem = [
                '@type'     => 'Event',
                'name'      => $ev->name,
                'url'       => route('events.show', $ev->slug),
                'startDate' => $ev->start_at ? $ev->start_at->format(DateTimeInterface::ISO8601) : null,
            ];
            if (!empty($ev->venue_id) && $ev->venue) {
                $eventItem['location'] = ['@type' => 'Place', 'name' => $ev->venue->name];
            } else {
                $eventItem['location'] = [
                    '@type'   => 'Place',
                    'name'    => 'TBA',
                    'address' => [
                        '@type'           => 'PostalAddress',
                        'addressLocality' => 'Pittsburgh',
                        'addressRegion'   => 'PA',
                        'addressCountry'  => 'US',
                    ],
                ];
            }
            $eventItems[] = $eventItem;
        }
        $jsonLd['event'] = $eventItems;
    }
@endphp
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode($breadcrumbJsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
