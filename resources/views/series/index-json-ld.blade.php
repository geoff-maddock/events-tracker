@php
    $items = [];
    $position = 1;
    foreach ($series as $item) {
        $listItem = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'item'     => [
                '@type' => 'EventSeries',
                'name'  => $item->name,
                'url'   => route('series.show', $item->slug),
            ],
        ];
        if ($item->short) {
            $listItem['item']['description'] = $item->short;
        }
        if ($item->venue) {
            $listItem['item']['location'] = [
                '@type' => 'Place',
                'name'  => $item->venue->name,
            ];
        }
        $items[] = $listItem;
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
