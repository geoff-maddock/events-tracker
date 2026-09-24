@php
    use App\Services\SeriesSchema;

    // One batched eager load for the whole page: series.index-tw is rendered by
    // seven controller methods whose eager loads differ, and the nodes below
    // read photos, venue locations and entity roles for every row.
    $seriesItems = $series instanceof \Illuminate\Contracts\Pagination\Paginator
        ? $series->getCollection()
        : $series;
    SeriesSchema::eagerLoad($seriesItems);

    $items = [];
    $position = 1;
    foreach ($seriesItems as $item) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            // No subEvent on a listing — the ItemList is already 48 nodes deep.
            'item'     => SeriesSchema::forSeries($item),
        ];
    }

    $jsonLd = [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => 'Series',
        'url'             => route('series.index'),
        'itemListElement' => $items,
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
</script>
