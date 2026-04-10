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

    $organizationJsonLd = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Organization',
        'name'        => config('app.app_name'),
        'url'         => config('app.url'),
        'description' => 'A calendar and guide to events, venues, artists, promoters, and series.',
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($websiteJsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode($organizationJsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
