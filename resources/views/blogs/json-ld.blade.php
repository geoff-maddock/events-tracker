@php
    $headline = $blog->name ?: config('app.name', 'Arcane City');
    $descriptionText = trim((string) preg_replace('/\s+/', ' ', strip_tags($blog->body ?? '')));
    $description = $descriptionText !== ''
        ? \Illuminate\Support\Str::limit($descriptionText, 200, '...')
        : $headline;

    $author = [
        '@type' => 'Person',
        'name' => $blog->user?->name ?: config('app.name', 'Arcane City'),
    ];

    if ($blog->user) {
        $author['url'] = url('/users/' . $blog->user->id);
    }

    $imageUrl = url('/images/arcane-city-promo.jpg');

    if (\Illuminate\Support\Facades\Schema::hasTable('blog_photo')) {
        $primaryPhoto = $blog->getPrimaryPhoto();
        if ($primaryPhoto) {
            $imageUrl = $primaryPhoto->getPath();
        }
    }

    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $headline,
        'name' => $headline,
        'description' => $description,
        'url' => route('blogs.show', $blog->slug),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => route('blogs.show', $blog->slug),
        ],
        'datePublished' => $blog->created_at?->format(DateTimeInterface::ISO8601),
        'dateModified' => $blog->updated_at?->format(DateTimeInterface::ISO8601),
        'author' => $author,
        'image' => [$imageUrl],
        'publisher' => [
            '@type' => 'Organization',
            'name' => config('app.name', 'Arcane City'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => url('/images/arcane-city-icon-96x96.png'),
            ],
        ],
    ], fn ($value) => $value !== null && $value !== '');
@endphp
<script type="application/ld+json">
{!! json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
