@php
    use App\Services\SeriesSchema;

    $canonicalUrl = route('series.show', $series);

    // $upcomingEvents comes from SeriesController::show(); older callers of
    // this partial passed only the paginated archive as $events.
    $instances = $upcomingEvents ?? collect($events ?? [])->filter(
        fn ($ev) => $ev->start_at && $ev->start_at->gte(now())
    );

    $seriesJsonLd = SeriesSchema::document($series, $instances);

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
