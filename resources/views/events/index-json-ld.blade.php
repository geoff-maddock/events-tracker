@php
    use App\Services\EventSchema;

    $items = [];
    $position = 1;
    foreach ($events as $event) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'item'     => EventSchema::forEvent($event, EventSchema::LISTING_PERFORMER_LIMIT),
        ];
    }

    $isTagPage  = isset($tag);
    $pageUrl    = $pageUrl ?? ($isTagPage ? route('events.tag', $tag->slug) : route('events.index'));
    $pageName   = $pageName ?? ($isTagPage ? (ucfirst($tag->name) . ' Events') : 'Events');
    $pageDesc   = $pageDesc ?? ($isTagPage
        ? ('Events tagged with ' . $tag->name . ' in Pittsburgh')
        : 'Upcoming events, concerts and shows in Pittsburgh');

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
    } elseif ($pageUrl !== route('events.index')) {
        $breadcrumbItems[] = [
            '@type'    => 'ListItem',
            'position' => 2,
            'name'     => $pageName,
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
