@php
    $websiteJsonLd = [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => config('app.app_name'),
        'url'      => config('app.url'),
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => config('app.url') . '/events?filters[name]={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $orgSameAs = array_values(array_filter([
        config('app.social_facebook')  ?: null,
        config('app.social_instagram') ?: null,
        config('app.social_twitter')   ?: null,
    ]));

    $organizationJsonLd = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Organization',
        'name'        => config('app.app_name'),
        'url'         => config('app.url'),
        'logo'        => config('app.url') . '/images/arcane-city-icon-96x96.png',
        'description' => 'A calendar and guide to events, venues, artists, promoters, and series.',
    ];

    if (!empty($orgSameAs)) {
        $organizationJsonLd['sameAs'] = $orgSameAs;
    }
@endphp
<script type="application/ld+json">
{!! json_encode($websiteJsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode($organizationJsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
