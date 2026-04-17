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

            // endDate — use end_at if set, otherwise start_at + DEFAULT_LENGTH hours
            if ($ev->default_end_time !== null) {
                $eventItem['endDate'] = $ev->default_end_time->format(DateTimeInterface::ISO8601);
            }

            // eventAttendanceMode
            $eventItem['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';

            // eventStatus — map Cancelled visibility to EventCancelled; everything else is EventScheduled
            $eventItem['eventStatus'] = ($ev->visibility && $ev->visibility->name === 'Cancelled')
                ? 'https://schema.org/EventCancelled'
                : 'https://schema.org/EventScheduled';

            // image
            if ($photo = $ev->getPrimaryPhoto()) {
                $eventItem['image'] = [Storage::disk('external')->url($photo->getStoragePath())];
            }

            // description
            if ($ev->short) {
                $eventItem['description'] = $ev->short;
            }

            // location — add PostalAddress when venue has a location record
            if (!empty($ev->venue_id) && $ev->venue) {
                $location = ['@type' => 'Place', 'name' => $ev->venue->name];
                if ($ev->venue->getPrimaryLocationAddress(true)) {
                    $loc = $ev->venue->locations->first();
                    if ($loc) {
                        $location['address'] = [
                            '@type'           => 'PostalAddress',
                            'streetAddress'   => $loc->address_one,
                            'addressLocality' => $loc->city,
                            'postalCode'      => $loc->postcode,
                            'addressRegion'   => $loc->state,
                            'addressCountry'  => $loc->country,
                        ];
                    }
                }
                $eventItem['location'] = $location;
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

            // offers
            $offerUrl = $ev->ticket_link ?? $ev->primary_link ?? route('events.show', $ev->slug);
            $offer = [
                '@type'        => 'Offer',
                'url'          => $offerUrl,
                'price'        => $ev->door_price ?? 0,
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
            ];
            if ($ev->created_at) {
                $offer['validFrom'] = $ev->created_at->format(DateTimeInterface::ISO8601);
            }
            $eventItem['offers'] = $offer;

            // performer — use related performer entities, fall back to event name
            $performers = $ev->performerEntities(10);
            if (count($performers) > 0) {
                $performerList = [];
                foreach ($performers as $performer) {
                    $p = ['@type' => 'PerformingGroup', 'name' => $performer->name];
                    if ($performer->primaryLink() !== null) {
                        $p['url'] = $performer->primaryLink()->url;
                    }
                    $performerList[] = $p;
                }
                $eventItem['performer'] = $performerList;
            } else {
                $fallback = ['@type' => 'PerformingGroup', 'name' => $ev->name];
                if ($ev->primary_link) {
                    $fallback['url'] = $ev->primary_link;
                }
                $eventItem['performer'] = [$fallback];
            }

            // organizer — prefer promoter, fall back to venue
            if (!empty($ev->promoter_id) && $ev->promoter) {
                $organizer = ['@type' => 'Organization', 'name' => $ev->promoter->name];
                if ($ev->promoter->primaryLink() !== null) {
                    $organizer['url'] = $ev->promoter->primaryLink()->url;
                }
                $eventItem['organizer'] = $organizer;
            } elseif (!empty($ev->venue_id) && $ev->venue) {
                $organizer = ['@type' => 'Organization', 'name' => $ev->venue->name];
                if ($ev->venue->primaryLink() !== null) {
                    $organizer['url'] = $ev->venue->primaryLink()->url;
                }
                $eventItem['organizer'] = $organizer;
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
