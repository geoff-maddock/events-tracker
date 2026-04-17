@php
    $items = [];
    $position = 1;
    $hasBlogPhotoTable = \Illuminate\Support\Facades\Schema::hasTable('blog_photo');

    foreach ($blogs as $blog) {
        $headline = $blog->name;
        $imageUrl = url('/images/arcane-city-promo.jpg');

        if ($hasBlogPhotoTable) {
            $primaryPhoto = $blog->getPrimaryPhoto();
            if ($primaryPhoto) {
                $imageUrl = $primaryPhoto->getPath();
            }
        }

        $listItem = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'item'     => [
                '@type'    => 'BlogPosting',
                'name'     => $headline,
                'headline' => $headline,
                'url'      => route('blogs.show', $blog->slug),
                'image'    => [$imageUrl],
            ],
        ];

        if ($blog->user) {
            $listItem['item']['author'] = [
                '@type' => 'Person',
                'name'  => $blog->user->name,
            ];
        }

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
