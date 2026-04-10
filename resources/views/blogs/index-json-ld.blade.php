@php
    $items = [];
    $position = 1;
    foreach ($blogs as $blog) {
        $listItem = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'item'     => [
                '@type' => 'BlogPosting',
                'name'  => $blog->title,
                'url'   => route('blogs.show', $blog->slug),
            ],
        ];
        if ($blog->created_at) {
            $listItem['item']['datePublished'] = $blog->created_at->format(DateTimeInterface::ISO8601);
        }
        $items[] = $listItem;
    }

    $jsonLd = [
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => 'Blog',
        'url'             => route('blogs.index'),
        'itemListElement' => $items,
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
