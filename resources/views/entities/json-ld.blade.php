@php
    $primaryPhoto = $entity->getPrimaryPhoto();
    $primaryLocation = $entity->getPrimaryLocation();
    $aliases = $entity->aliases->pluck('name')->filter()->values();
    $tags = $entity->tags->pluck('name')->filter()->values();

    $sameAs = [];
    if (!empty($entity->instagram_username)) {
        $sameAs[] = 'https://www.instagram.com/' . $entity->instagram_username;
    }
    if (!empty($entity->facebook_username)) {
        $sameAs[] = 'https://www.facebook.com/' . $entity->facebook_username;
    }
    if (!empty($entity->twitter_username)) {
        $sameAs[] = 'https://twitter.com/' . $entity->twitter_username;
    }
    foreach ($entity->links as $link) {
        if (!empty($link->url) && (str_contains($link->url, 'soundcloud.com') || str_contains($link->url, 'bandcamp.com'))) {
            $sameAs[] = $link->url;
        }
    }
    $sameAs = array_values(array_unique($sameAs));
@endphp
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "{{ $entity->getSchemaType() }}",
    "name": "{{ $entity->name }}"
    @if ($aliases->isNotEmpty())
    ,"alternateName": {!! json_encode($aliases->all()) !!}
    @endif
    ,"url": "{{ url('/entities/' . $entity->slug) }}"
    @if ($primaryPhoto)
    ,"image": "{{ Storage::disk('external')->url($primaryPhoto->getStoragePath()) }}"
    @endif
    ,"description": "{{ addslashes($entity->getSeoDescriptionFormat()) }}"
    @if ($tags->isNotEmpty())
    ,"genre": {!! json_encode($tags->all()) !!}
    @endif
    @if ($primaryLocation && !empty($primaryLocation->city))
    ,"location": {
        "@type": "Place",
        "name": "{{ $primaryLocation->city }}{{ !empty($primaryLocation->state) ? ', ' . $primaryLocation->state : '' }}"
        @if (!empty($primaryLocation->address_one))
        ,"address": {
            "@type": "PostalAddress",
            "streetAddress": "{{ $primaryLocation->address_one }}",
            "addressLocality": "{{ $primaryLocation->city }}",
            "addressRegion": "{{ $primaryLocation->state }}",
            "postalCode": "{{ $primaryLocation->postcode }}",
            "addressCountry": "{{ $primaryLocation->country }}"
        }
        @endif
    }
    @endif
    @if (!empty($sameAs))
    ,"sameAs": {!! json_encode($sameAs) !!}
    @endif
}
</script>
