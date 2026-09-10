@php
    use App\Services\SeriesSchema;

    $items = [];
    $position = 1;
    foreach ($series as $item) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
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
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
