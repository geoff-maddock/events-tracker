<?php

namespace App\Services;

use App\Models\Entity;
use Illuminate\Support\Facades\Storage;

/**
 * Maps an Entity onto schema.org JSON-LD for its own page: the entity node
 * and its breadcrumbs. EventSchema and SeriesSchema do the same for the other
 * two; an entity inside an event (venue, performer, organizer) is built by
 * EventSchema.
 */
class EntitySchema
{
    /**
     * The schema.org type for the entity: by role first, then by entity type.
     */
    public static function type(Entity $entity): string
    {
        if ($entity->hasRole('Venue')) { return 'MusicVenue'; }
        if ($entity->hasRole('Band')) { return 'MusicGroup'; }
        if ($entity->hasRole('DJ') || $entity->hasRole('Producer') || $entity->hasRole('Musician')) { return 'MusicGroup'; }
        if ($entity->hasRole('Artist')) { return 'Person'; }
        if ($entity->hasRole('Promoter')) { return 'Organization'; }
        if ($entity->hasRole('Shop')) { return 'LocalBusiness'; }

        if ($entity->entityType) {
            return match ($entity->entityType->name) {
                'Individual' => 'Person',
                'Group'      => 'Organization',
                'Space'      => 'LocalBusiness',
                default      => 'Organization',
            };
        }

        return 'Organization';
    }

    /**
     * The entity's profiles elsewhere: social usernames, links on known
     * music platforms and the primary link.
     *
     * @return array<int, string>
     */
    public static function sameAs(Entity $entity): array
    {
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

        $identityPlatforms = [
            'soundcloud.com', 'bandcamp.com', 'ra.co', 'residentadvisor.net',
            'discogs.com', 'open.spotify.com', 'spotify.com', 'mixcloud.com',
            'beatport.com', 'youtube.com', 'facebook.com',
        ];

        foreach ($entity->links as $link) {
            // only well-formed http(s) URLs (#2220)
            if (!$url = $link->safeUrl()) {
                continue;
            }
            foreach ($identityPlatforms as $platform) {
                if (str_contains($url, $platform)) {
                    $sameAs[] = $url;
                    break;
                }
            }
        }

        $primary = $entity->primaryLink();
        $primaryUrl = $primary?->safeUrl();
        if ($primaryUrl && !in_array($primaryUrl, $sameAs)) {
            $sameAs[] = $primaryUrl;
        }

        return array_values(array_unique($sameAs));
    }

    /**
     * The JSON-LD node for the entity's own page.
     *
     * @return array<string, mixed>
     */
    public static function document(Entity $entity): array
    {
        $schemaType   = self::type($entity);
        $canonicalUrl = url('/entities/' . $entity->slug);

        $data = [
            '@context'         => 'https://schema.org',
            '@type'            => $schemaType,
            '@id'              => $canonicalUrl . '#entity',
            'name'             => $entity->name,
            'url'              => $canonicalUrl,
            'description'      => $entity->getSeoDescriptionFormat(),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonicalUrl],
        ];

        $aliases = $entity->aliases->pluck('name')->filter()->values()->all();
        if (!empty($aliases)) {
            $data['alternateName'] = $aliases;
        }

        $photo = $entity->getPrimaryPhoto();
        if ($photo) {
            $data['image'] = Storage::disk('external')->url($photo->getStoragePath());
        }

        $tags = $entity->tags->pluck('name')->filter()->values()->all();
        if (!empty($tags)) {
            if (in_array($schemaType, ['MusicGroup', 'MusicVenue'])) {
                $data['genre'] = $tags;
            } else {
                $data['knowsAbout'] = $tags;
            }
        }

        if ($schemaType === 'Person') {
            $roles = $entity->roles->pluck('name')->filter()->values()->all();
            if (!empty($roles)) {
                $data['hasOccupation'] = array_map(
                    fn ($role) => ['@type' => 'Occupation', 'name' => $role],
                    $roles
                );
            }
        }

        $location = $entity->getPrimaryLocation();
        // JSON-LD is built with no request/auth context, so
        // match getSeoDescriptionFormat()'s convention: treat a Guarded
        // location as not publicly disclosable and omit it, same as the
        // facts panel / Locations card do for guests. Shared with
        // App\Services\EventSchema via Location::isGuarded().
        $locationIsGuarded = $location && $location->isAddressGuarded();

        if ($location && !empty($location->city) && !($schemaType === 'MusicVenue' && $locationIsGuarded)) {
            if ($schemaType === 'MusicVenue' && !empty($location->address_one)) {
                // Shared with App\Services\EventSchema, which normalises the
                // free-text country/region columns and drops empty parts
                // instead of publishing them as empty strings.
                $data['address'] = EventSchema::postalAddress($location);
            } else {
                $cityState = $location->city . (!empty($location->state) ? ', ' . $location->state : '');
                $data['homeLocation'] = ['@type' => 'Place', 'name' => $cityState];
            }
        }

        if ($schemaType === 'MusicVenue' && $location && !empty($location->capacity) && !$locationIsGuarded) {
            $data['maximumAttendeeCapacity'] = (int) $location->capacity;
        }

        $sameAs = self::sameAs($entity);
        if (!empty($sameAs)) {
            $data['sameAs'] = $sameAs;
        }

        return $data;
    }

    /**
     * The BreadcrumbList for the entity's own page.
     *
     * @return array<string, mixed>
     */
    public static function breadcrumbs(Entity $entity): array
    {
        $items   = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Entities', 'item' => url('/entities')]];
        $primary = $entity->roles->first();

        if ($primary) {
            $items[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $primary->name, 'item' => route('entities.role', $primary->slug)];
            $items[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $entity->name,    'item' => url('/entities/' . $entity->slug)];
        } else {
            $items[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $entity->name, 'item' => url('/entities/' . $entity->slug)];
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
}
