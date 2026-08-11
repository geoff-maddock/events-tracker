@php
    use App\Services\EventSchema;

    $jsonLd           = $entity->getJsonLd();
    $breadcrumbJsonLd = $entity->getBreadcrumbJsonLd();

    // Add upcoming events array — powers the Google event carousel on venue/artist pages
    if (!empty($relatedEvents) && count($relatedEvents) > 0) {
        $eventItems = [];
        foreach ($relatedEvents as $ev) {
            $eventItems[] = EventSchema::forEvent($ev, EventSchema::LISTING_PERFORMER_LIMIT);
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
