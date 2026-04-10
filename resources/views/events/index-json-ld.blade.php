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
        }
        if ($event->short) {
            $item['item']['description'] = $event->short;
        }
        $items[] = $item;
    }

    $jsonLd = [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => 'Events',
        'url'             => route('events.index'),
        'itemListElement' => $items,
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
