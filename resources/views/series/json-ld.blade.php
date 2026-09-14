@php
    use App\Services\SeriesSchema;

    // EventSeries is an Event subtype, so Google holds it to the full Event
    // field list — App\Services\SeriesSchema emits all of it, with the same
    // fallback policy EventSchema uses for a single event.
    $seriesJsonLd = SeriesSchema::document($series, $upcomingEvents ?? $events ?? []);

    $canonicalUrl = route('series.show', $series);

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
