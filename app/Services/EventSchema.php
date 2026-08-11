<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Event;

/**
 * The one place that maps an Event onto a schema.org Event node.
 *
 * Four templates grew their own copy of this mapping — the event detail page,
 * venue/artist pages, series subEvents, and the listing ItemList — and they had
 * drifted: the listing emitted four properties where the others emitted a
 * dozen, which is what Search Console reports as missing fields on
 * /events/this-week. They also disagreed about whether a Guarded venue's street
 * address is publishable. Every caller now shares this builder instead.
 *
 * Dates go through App\Services\EventTime rather than reading the cast Carbon
 * directly. config('app.timezone') is 'EST', a fixed UTC-5 offset that never
 * observes daylight saving, so a Carbon taken straight off the model names an
 * instant an hour later than intended from March to November. That is invisible
 * while a value is only formatted for display, and wrong the moment it leaves
 * the app as an absolute instant — which is exactly what JSON-LD is.
 */
class EventSchema
{
    public const CONTEXT = 'https://schema.org';

    /** Performers emitted per event on listing pages, where a page holds 48 of them. */
    public const LISTING_PERFORMER_LIMIT = 5;

    /** Performers emitted on a single-event page. */
    public const DETAIL_PERFORMER_LIMIT = 10;

    /**
     * A standalone Event document, for a page whose subject is one event.
     *
     * @return array<string, mixed>
     */
    public static function document(Event $event, int $performerLimit = self::DETAIL_PERFORMER_LIMIT): array
    {
        return ['@context' => self::CONTEXT] + self::forEvent($event, $performerLimit);
    }

    /**
     * The Event node, with no @context — for embedding in an ItemList, a
     * venue's event[] array or a series' subEvent[] array.
     *
     * Relations read here: photos, visibility, venue.locations, venue.links,
     * promoter.links, entities.roles, entities.links. The helpers this calls
     * all short-circuit on relationLoaded(), so callers rendering many events
     * should eager load them — see EventsController::cardEventEagerLoad().
     *
     * @return array<string, mixed>
     */
    public static function forEvent(Event $event, int $performerLimit = self::DETAIL_PERFORMER_LIMIT): array
    {
        $url = route('events.show', $event->slug);

        $node = [
            '@type' => 'Event',
            'name'  => $event->name,
            'url'   => $url,
        ];

        // An event with no start_at is already unpublishable as an Event;
        // omit the key rather than emit a null, which reads as a malformed
        // date rather than an absent one.
        if ($startDate = EventTime::startsAt($event)) {
            $node['startDate'] = $startDate->toAtomString();
        }

        if ($endDate = EventTime::endsAt($event)) {
            $node['endDate'] = $endDate->toAtomString();
        }

        $node['eventAttendanceMode'] = self::CONTEXT.'/OfflineEventAttendanceMode';
        $node['eventStatus'] = self::eventStatus($event);

        if ($image = $event->getPrimaryPhotoPath()) {
            $node['image'] = [$image];
        }

        if ($description = self::description($event)) {
            $node['description'] = $description;
        }

        $node['location'] = self::location($event->venue);
        $node['offers'] = self::offers($event, $url);
        $node['performer'] = self::performers($event, $performerLimit);

        if ($organizer = self::organizer($event)) {
            $node['organizer'] = $organizer;
        }

        return $node;
    }

    /**
     * Cancellation is recorded two ways and either one counts. There is no
     * postponed concept in the schema, so everything else is scheduled.
     */
    protected static function eventStatus(Event $event): string
    {
        $cancelled = null !== $event->cancelled_at
            || 'Cancelled' === $event->visibility?->name;

        return self::CONTEXT.($cancelled ? '/EventCancelled' : '/EventScheduled');
    }

    /**
     * Prefer the short blurb; fall back to the long description. Both may hold
     * markup, which schema.org descriptions should not.
     */
    protected static function description(Event $event): ?string
    {
        $text = $event->short ?: $event->description;

        if (null === $text || '' === trim($text)) {
            return null;
        }

        return trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '') ?: null;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function location(?Entity $venue): array
    {
        if (null === $venue) {
            // Everything published here is a Pittsburgh event, so a venueless
            // listing is still locatable to the city.
            return [
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

        $place = [
            '@type' => 'Place',
            'name'  => $venue->name,
            'url'   => route('entities.show', $venue->slug),
        ];

        $location = $venue->getPrimaryLocation();

        // A Guarded address is deliberately withheld from crawlers — same rule
        // Entity::getJsonLd() applies to the venue's own page.
        if ($location && !$location->isAddressGuarded() && !empty($location->address_one)) {
            $place['address'] = [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $location->address_one,
                'addressLocality' => $location->city ?? 'Pittsburgh',
                'addressRegion'   => $location->state ?? 'PA',
                'postalCode'      => $location->postcode ?? '',
                'addressCountry'  => $location->country ?? 'US',
            ];
        }

        return $place;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function offers(Event $event, string $eventUrl): array
    {
        $offer = [
            '@type'         => 'Offer',
            'url'           => $event->ticket_link ?: ($event->primary_link ?: $eventUrl),
            'price'         => (string) ($event->door_price ?? $event->presale_price ?? 0),
            'priceCurrency' => 'USD',
            'availability'  => self::CONTEXT.'/InStock',
        ];

        if ($validFrom = EventTime::toInstant($event->created_at)) {
            $offer['validFrom'] = $validFrom->toAtomString();
        }

        return $offer;
    }

    /**
     * Related dj/band/producer entities, falling back to the event itself so
     * the property is never empty — Google treats a performerless Event as
     * incomplete.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function performers(Event $event, int $limit): array
    {
        $performers = [];

        foreach ($event->performerEntities($limit) as $entity) {
            $performer = ['@type' => 'PerformingGroup', 'name' => $entity->name];

            if ($link = $entity->primaryLink()) {
                $performer['url'] = $link->url;
            }

            $performers[] = $performer;
        }

        if (!empty($performers)) {
            return $performers;
        }

        $fallback = ['@type' => 'PerformingGroup', 'name' => $event->name];
        if ($event->primary_link) {
            $fallback['url'] = $event->primary_link;
        }

        return [$fallback];
    }

    /**
     * The promoter runs the show; absent one, the venue is the closest thing
     * to an organizer we can name.
     *
     * @return array<string, mixed>|null
     */
    protected static function organizer(Event $event): ?array
    {
        $entity = $event->promoter ?: $event->venue;

        if (null === $entity) {
            return null;
        }

        $organizer = ['@type' => 'Organization', 'name' => $entity->name];

        if ($link = $entity->primaryLink()) {
            $organizer['url'] = $link->url;
        }

        return $organizer;
    }
}
