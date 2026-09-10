<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Location;
use Illuminate\Support\Facades\Storage;

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
 * Every recommended Event property Google lists is emitted for every event,
 * falling back to the best available stand-in rather than omitting the key.
 * Search Console counts each missing recommended field as an enrichment
 * warning per indexed item; the September 2026 export had ~3,300 of them,
 * almost all from organizers with no external link and venues with no
 * street address on file. The fallbacks are documented at each helper.
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
     * The site-wide promo image, already the default og:image in the layout.
     * Last resort for an event with no flyer whose series and venue have no
     * photo either.
     */
    public const DEFAULT_IMAGE_PATH = '/images/arcane-city-promo.jpg';

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
     * The series relation (and venue/series photos) are only read on the
     * fallback paths for an event with no photo or no promoter and venue,
     * which is rare enough not to warrant an eager load of its own.
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
        $node['image'] = [self::image($event)];
        $node['description'] = self::description($event);
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
     * The event's own flyer, else the series' image, else the venue's, else
     * the site promo image. Google wants a relevant image on every Event and
     * a generic one only clears the warning — the flyer is what improves the
     * result — but an absent image is the worst of the options.
     */
    protected static function image(Event $event): string
    {
        if ($path = $event->getPrimaryPhotoPath()) {
            return $path;
        }

        $photo = $event->series?->getPrimaryPhoto() ?? $event->venue?->getPrimaryPhoto();

        if ($photo) {
            return Storage::disk('external')->url($photo->getStoragePath());
        }

        return url(self::DEFAULT_IMAGE_PATH);
    }

    /**
     * Prefer the short blurb; fall back to the long description. Both may hold
     * markup, which schema.org descriptions should not. An event with no copy
     * at all gets a sentence built from what is known about it, so the field
     * is never absent.
     */
    protected static function description(Event $event): string
    {
        $text = $event->short ?: $event->description;

        if (null !== $text && '' !== trim($text)) {
            $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');

            if ('' !== $clean) {
                return $clean;
            }
        }

        $sentence = $event->name;

        if ($event->venue) {
            $sentence .= ' at '.$event->venue->name;
        }

        if ($startsAt = EventTime::startsAt($event)) {
            $sentence .= ' on '.$startsAt->format('l, F j, Y');
        }

        return $sentence.'.';
    }

    /**
     * The venue as a Place. Always carries a PostalAddress: the full street
     * address when one is on file and publishable, otherwise the city level,
     * which is still what Google asks for and beats nothing. Roughly a fifth
     * of listed venues have no Location row at all.
     *
     * @return array<string, mixed>
     */
    public static function location(?Entity $venue): array
    {
        if (null === $venue) {
            // Everything published here is a Pittsburgh event, so a venueless
            // listing is still locatable to the city.
            return [
                '@type'   => 'Place',
                'name'    => 'TBA',
                'address' => self::postalAddress(null),
            ];
        }

        $place = [
            '@type' => 'Place',
            'name'  => $venue->name,
            'url'   => route('entities.show', $venue->slug),
        ];

        $location = $venue->getPrimaryLocation();

        // A Guarded address is deliberately withheld from crawlers — same rule
        // Entity::getJsonLd() applies to the venue's own page. Nothing about
        // it is published, not even the city, so the node cannot be used to
        // narrow the venue down.
        if ($location && $location->isAddressGuarded()) {
            return $place;
        }

        $place['address'] = self::postalAddress($location);

        return $place;
    }

    /**
     * A PostalAddress from a Location, or the city-level default when there
     * is no Location or it carries no street. Empty parts are omitted rather
     * than emitted as empty strings.
     *
     * @return array<string, string>
     */
    protected static function postalAddress(?Location $location): array
    {
        $address = ['@type' => 'PostalAddress'];

        if ($location && !empty($location->address_one)) {
            $address['streetAddress'] = $location->address_one;
        }

        $address['addressLocality'] = $location?->city ?: 'Pittsburgh';
        $address['addressRegion'] = $location?->state ?: 'PA';

        if ($location && !empty($location->postcode)) {
            $address['postalCode'] = $location->postcode;
        }

        $address['addressCountry'] = $location?->country ?: 'US';

        return $address;
    }

    /**
     * The ticket link, else the event's primary link, else its own page —
     * but only a link that is actually an absolute http(s) URL. Older rows
     * hold values like "Ticketfly.com" or "Admission: Free" from before the
     * form validated the field, and Search Console flags those as invalid.
     *
     * price is only emitted when one is on file. Google renders a price of
     * 0 as "Free", and most events with no recorded price are ticketed shows
     * whose price simply was not entered — advertising those as free is
     * worse than an Offer without a price.
     *
     * @return array<string, mixed>
     */
    protected static function offers(Event $event, string $eventUrl): array
    {
        $offer = [
            '@type'        => 'Offer',
            'url'          => self::validUrl($event->ticket_link) ?? self::validUrl($event->primary_link) ?? $eventUrl,
            'availability' => self::CONTEXT.'/InStock',
        ];

        $price = $event->door_price ?? $event->presale_price;

        if (null !== $price && '' !== $price) {
            $offer['price'] = (string) $price;
            $offer['priceCurrency'] = 'USD';
        }

        if ($validFrom = EventTime::toInstant($event->created_at)) {
            $offer['validFrom'] = $validFrom->toAtomString();
        }

        return $offer;
    }

    /**
     * The value if it is an absolute http or https URL, otherwise null.
     * filter_var rejects non-ASCII hosts, which also catches the homoglyph
     * domains that Search Console reports as invalid.
     */
    public static function validUrl(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim($value);

        if ('' === $value || false === filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $value : null;
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
            $performers[] = self::performer($entity);
        }

        if (!empty($performers)) {
            return $performers;
        }

        $fallback = ['@type' => 'PerformingGroup', 'name' => $event->name];
        if ($url = self::validUrl($event->primary_link)) {
            $fallback['url'] = $url;
        }

        return [$fallback];
    }

    /**
     * A performing entity as a PerformingGroup with its own page as the url
     * when it has no external link.
     *
     * @return array<string, string>
     */
    public static function performer(Entity $entity): array
    {
        return [
            '@type' => 'PerformingGroup',
            'name'  => $entity->name,
            'url'   => self::entityUrl($entity),
        ];
    }

    /**
     * The promoter runs the show; absent one, the venue is the closest thing
     * to an organizer we can name; absent both, the series the event belongs
     * to may name either.
     *
     * @return array<string, mixed>|null
     */
    protected static function organizer(Event $event): ?array
    {
        $entity = $event->promoter
            ?: $event->venue
            ?: $event->series?->promoter
            ?: $event->series?->venue;

        if (null === $entity) {
            return null;
        }

        return self::organization($entity);
    }

    /**
     * An entity as an Organization with a url that is always present.
     *
     * @return array<string, string>
     */
    public static function organization(Entity $entity): array
    {
        return [
            '@type' => 'Organization',
            'name'  => $entity->name,
            'url'   => self::entityUrl($entity),
        ];
    }

    /**
     * The entity's own site when it has a valid primary link, else its page
     * here. Search Console's single largest warning was organizers with a
     * name and no url: most venues and promoters have no primary link set,
     * and their arcane.city page is a real, crawlable url for them.
     */
    public static function entityUrl(Entity $entity): string
    {
        return self::validUrl($entity->primaryLink()?->url) ?? route('entities.show', $entity->slug);
    }
}
