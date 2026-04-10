@php
    $items = [];
    $position = 1;
    foreach ($entities as $entity) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'item'     => [
                '@type' => $entity->getSchemaType(),
                'name'  => $entity->name,
                'url'   => route('entities.show', $entity->slug),
            ],
        ];
    }

    $jsonLd = [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => 'Entities',
        'url'             => route('entities.index'),
        'itemListElement' => $items,
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
