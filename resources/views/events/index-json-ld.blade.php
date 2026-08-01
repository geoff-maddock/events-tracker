@php
    $items = [];
    $position = 1;
    foreach ($events as $event) {
        $item = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'item'     => [
                '@type'     => 'Event',
                'name'      => $event->name,
                'startDate' => $event->start_at ? $event->start_at->format(DateTimeInterface::ISO8601) : null,
                'url'       => route('events.show', $event->slug),
            ],
        ];
        if ($event->venue) {
            $item['item']['location'] = [
                '@type' => 'Place',
                'name'  => $event->venue->name,
            ];
        } else {
            $item['item']['location'] = [
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
        if ($event->short) {
            $item['item']['description'] = $event->short;
        }
        $items[] = $item;
    }

    $isTagPage  = isset($tag);
    $pageUrl    = $isTagPage ? route('events.tag', $tag->slug) : route('events.index');
    $pageName   = $isTagPage ? (ucfirst($tag->name) . ' Events') : 'Events';
    $pageDesc   = $isTagPage
        ? ('Events tagged with ' . $tag->name . ' in Pittsburgh')
        : 'Upcoming events, concerts and shows in Pittsburgh';

    $jsonLd = [
        '@context'    => 'https://schema.org',
        '@type'       => 'CollectionPage',
        'name'        => $pageName,
        'url'         => $pageUrl,
        'description' => $pageDesc,
        'mainEntity'  => [
            '@type'           => 'ItemList',
            'itemListElement' => $items,
        ],
    ];

    $breadcrumbItems = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Events', 'item' => route('events.index')],
    ];
    if ($isTagPage) {
        $breadcrumbItems[] = [
            '@type'    => 'ListItem',
            'position' => 2,
            'name'     => ucfirst($tag->name),
            'item'     => $pageUrl,
        ];
    }
    $breadcrumbJsonLd = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $breadcrumbItems,
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode($breadcrumbJsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
