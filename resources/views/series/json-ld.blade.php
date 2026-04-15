@php
    $canonicalUrl = route('series.show', $series->slug);

    $seriesJsonLd = [
        '@context'         => 'https://schema.org',
        '@type'            => 'EventSeries',
        '@id'              => $canonicalUrl . '#series',
        'name'             => $series->name,
        'url'              => $canonicalUrl,
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
    ];

    if ($series->short) {
        $seriesJsonLd['description'] = $series->short;
    }

    if ($photo = $series->getPrimaryPhoto()) {
        $seriesJsonLd['image'] = Storage::disk('external')->url($photo->getStoragePath());
    }

    if ($series->venue) {
        $seriesJsonLd['location'] = [
            '@type' => 'Place',
            'name'  => $series->venue->name,
            'url'   => route('entities.show', $series->venue->slug),
        ];
        $venueLocation = $series->venue->getPrimaryLocation();
        if ($venueLocation && !empty($venueLocation->address_one)) {
            $seriesJsonLd['location']['address'] = [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $venueLocation->address_one,
                'addressLocality' => $venueLocation->city,
                'addressRegion'   => $venueLocation->state ?? '',
                'postalCode'      => $venueLocation->postcode ?? '',
                'addressCountry'  => $venueLocation->country ?? 'US',
            ];
        }
    }

    // Add upcoming instances as subEvents
    if (isset($events) && count($events) > 0) {
        $subEvents = [];
        foreach ($events as $ev) {
            if (!$ev->start_at || $ev->start_at->lt(now())) {
                continue;
            }
            $subEvent = [
                '@type'     => 'Event',
                'name'      => $ev->name,
                'url'       => route('events.show', $ev->slug),
                'startDate' => $ev->start_at->format(DateTimeInterface::ISO8601),
            ];
            if (!empty($ev->venue_id) && $ev->venue) {
                $subEvent['location'] = ['@type' => 'Place', 'name' => $ev->venue->name];
            } elseif ($series->venue) {
                $subEvent['location'] = ['@type' => 'Place', 'name' => $series->venue->name];
            } else {
                $subEvent['location'] = [
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
            $subEvents[] = $subEvent;
        }
        if (!empty($subEvents)) {
            $seriesJsonLd['subEvent'] = $subEvents;
        }
    }

    $breadcrumbJsonLd = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Series', 'item' => route('series.index')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $series->name, 'item' => $canonicalUrl],
        ],
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($seriesJsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode($breadcrumbJsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
