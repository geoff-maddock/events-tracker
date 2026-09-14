<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Maps a Series onto a schema.org EventSeries node.
 *
 * EventSeries is a subtype of Event, so Google's Events rich result holds it
 * to the same field list — and the series templates were emitting five
 * properties where App\Services\EventSchema emits a dozen. The September 2026
 * Search Console export counted 52 items each missing endDate, offers,
 * eventStatus and performer, which is the number of series detail pages in the
 * sitemap; the ~135 missing organizer and ~60 missing image overlap the same
 * pages. Everything here mirrors the fallback policy EventSchema documents:
 * emit every recommended property for every series, standing in the best
 * available value rather than dropping the key.
 *
 * A series is a recurring thing with no single date, so its startDate is the
 * next instantiated event's — the date a searcher would act on. Dates go
 * through App\Services\EventTime for the fixed-offset 'EST' reason spelled out
 * in that class.
 *
 * Shared with EventSchema rather than reimplemented: location(), organization(),
 * performer(), entityUrl(), validUrl(), CONTEXT, CURRENCY, DEFAULT_IMAGE_PATH.
 */
class SeriesSchema
{
    /** Performers emitted per series. Mirrors EventSchema's listing limit. */
    public const PERFORMER_LIMIT = 5;

    /** Roles that make a related entity a performer rather than a promoter/venue. */
    public const PERFORMER_ROLES = ['dj', 'band', 'producer'];

    /**
     * Relations every node below reads — see eagerLoad().
     *
     * @var array<int, string>
     */
    public const EAGER_LOAD = [
        'photos', 'visibility', 'occurrenceType', 'occurrenceWeek', 'occurrenceDay', 'upcomingEvent', 'latestEvent',
        // .links feeds EventSchema::entityUrl(), which every organizer and
        // performer node calls — without it each one is its own query.
        'promoter.links', 'venue.links', 'venue.locations', 'venue.photos', 'entities.roles', 'entities.links',
    ];

    /**
     * A standalone EventSeries document, for a page whose subject is one series.
     *
     * @param iterable<int, Event> $upcomingEvents instances to emit as subEvent
     *
     * @return array<string, mixed>
     */
    public static function document(Series $series, iterable $upcomingEvents = []): array
    {
        return ['@context' => EventSchema::CONTEXT] + self::forSeries($series, $upcomingEvents);
    }

    /**
     * The EventSeries node, with no @context — for embedding in an ItemList.
     *
     * @param iterable<int, Event> $upcomingEvents
     *
     * @return array<string, mixed>
     */
    public static function forSeries(Series $series, iterable $upcomingEvents = []): array
    {
        $url = route('series.show', $series->slug);

        $node = [
            '@type'            => 'EventSeries',
            '@id'              => $url.'#series',
            'name'             => $series->name,
            'url'              => $url,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
        ];

        // A series with no upcoming instance still has a date worth stating,
        // and omitting startDate entirely makes the node unusable as an Event.
        // Only a series with no instance and no dates of its own drops the key.
        $instance = $series->nextEvent() ?? $series->latestEvent;

        if ($startDate = self::startDate($series, $instance)) {
            $node['startDate'] = $startDate->toAtomString();

            $endDate = $instance ? EventTime::endsAt($instance) : EventTime::toInstant($series->end_at);
            $node['endDate'] = ($endDate ?? $startDate->addHours(Event::DEFAULT_LENGTH))->toAtomString();
        }

        $node['eventAttendanceMode'] = EventSchema::CONTEXT.'/OfflineEventAttendanceMode';
        $node['eventStatus'] = self::eventStatus($series);
        $node['image'] = [self::image($series)];
        $node['description'] = self::description($series);
        $node['location'] = EventSchema::location($series->venue);
        $node['offers'] = self::offers($series, $url);
        $node['performer'] = self::performers($series);

        if ($organizer = self::organizer($series)) {
            $node['organizer'] = $organizer;
        }

        $subEvents = self::subEvents($series, $upcomingEvents);

        if (!empty($subEvents)) {
            $node['subEvent'] = $subEvents;
        }

        return $node;
    }

    /**
     * Eager load everything the node reads, in one pass over a collection.
     * The index view renders through seven different controller methods with
     * seven different eager loads, so the view batches them itself rather
     * than each method remembering to.
     *
     * @param iterable<int, Series> $series
     */
    public static function eagerLoad(iterable $series): void
    {
        $collection = $series instanceof Collection
            ? $series
            : new Collection(is_array($series) ? $series : iterator_to_array($series));

        if ($collection->isNotEmpty()) {
            $collection->loadMissing(self::EAGER_LOAD);
        }
    }

    /**
     * The next instantiated event's start — what a searcher would act on —
     * else, for a dormant series, the most recent one that did happen, else
     * the series' own start or the date it was founded.
     */
    protected static function startDate(Series $series, ?Event $instance): ?CarbonImmutable
    {
        if ($instance) {
            return EventTime::startsAt($instance);
        }

        return EventTime::toInstant($series->start_at)
            ?? EventTime::toInstant($series->founded_at);
    }

    /**
     * Same two ways of recording cancellation EventSchema reads on an event.
     */
    protected static function eventStatus(Series $series): string
    {
        $cancelled = null !== $series->cancelled_at
            || 'Cancelled' === $series->visibility?->name;

        return EventSchema::CONTEXT.($cancelled ? '/EventCancelled' : '/EventScheduled');
    }

    /**
     * The series' own image, else its venue's, else the site promo image.
     */
    protected static function image(Series $series): string
    {
        $photo = $series->getPrimaryPhoto() ?? $series->venue?->getPrimaryPhoto();

        if ($photo) {
            return Storage::disk('external')->url($photo->getStoragePath());
        }

        return url(EventSchema::DEFAULT_IMAGE_PATH);
    }

    /**
     * The short blurb, else the long description, else a sentence built from
     * what is known — the field is never absent. Markup is stripped; a
     * schema.org description is plain text.
     */
    protected static function description(Series $series): string
    {
        $text = $series->short ?: $series->description;

        if (null !== $text && '' !== trim($text)) {
            $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');

            if ('' !== $clean) {
                return $clean;
            }
        }

        $sentence = $series->name;

        if ($series->venue) {
            $sentence .= ' at '.$series->venue->name;
        }

        $repeat = trim((string) $series->occurrence_repeat);

        if ('' !== $repeat) {
            $sentence .= ', '.lcfirst($repeat);
        }

        return $sentence.'.';
    }

    /**
     * The ticket link, else the primary link, else the series' own page —
     * validated the same way EventSchema validates an event's, since the
     * columns took free text for as long.
     *
     * priceCurrency is unconditional and price is not, for the reasons
     * EventSchema::offers() documents.
     *
     * @return array<string, mixed>
     */
    protected static function offers(Series $series, string $seriesUrl): array
    {
        $offer = [
            '@type'         => 'Offer',
            'url'           => EventSchema::validUrl($series->ticket_link) ?? EventSchema::validUrl($series->primary_link) ?? $seriesUrl,
            'priceCurrency' => EventSchema::CURRENCY,
            'availability'  => EventSchema::CONTEXT.'/InStock',
        ];

        $price = $series->door_price ?? $series->presale_price;

        if (null !== $price && '' !== $price) {
            $offer['price'] = (string) $price;
        }

        if ($validFrom = EventTime::toInstant($series->created_at)) {
            $offer['validFrom'] = $validFrom->toAtomString();
        }

        return $offer;
    }

    /**
     * Related dj/band/producer entities, falling back to the series itself so
     * the property is never empty — the same shape EventSchema uses.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function performers(Series $series): array
    {
        $performers = [];

        foreach (self::performerEntities($series) as $entity) {
            $performers[] = EventSchema::performer($entity);
        }

        if (!empty($performers)) {
            return $performers;
        }

        $fallback = ['@type' => 'PerformingGroup', 'name' => $series->name];

        if ($url = EventSchema::validUrl($series->primary_link)) {
            $fallback['url'] = $url;
        }

        return [$fallback];
    }

    /**
     * Mirrors Event::performerEntities(): filter in memory when the relations
     * are already loaded, query only when they are not.
     *
     * @return iterable<int, Entity>
     */
    protected static function performerEntities(Series $series): iterable
    {
        if ($series->relationLoaded('entities') && $series->entities->every(fn ($e) => $e->relationLoaded('roles'))) {
            return $series->entities
                ->filter(fn ($entity) => $entity->roles->whereIn('slug', self::PERFORMER_ROLES)->isNotEmpty())
                ->sortBy('name')
                ->take(self::PERFORMER_LIMIT)
                ->values();
        }

        return $series->entities()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', self::PERFORMER_ROLES))
            ->orderBy('name')
            ->limit(self::PERFORMER_LIMIT)
            ->get();
    }

    /**
     * The promoter runs the series; absent one, the venue is the closest
     * thing to an organizer we can name.
     *
     * @return array<string, mixed>|null
     */
    protected static function organizer(Series $series): ?array
    {
        $entity = $series->promoter ?: $series->venue;

        return $entity ? EventSchema::organization($entity) : null;
    }

    /**
     * Upcoming instances as full Event nodes.
     *
     * @param iterable<int, Event> $events
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function subEvents(Series $series, iterable $events): array
    {
        $subEvents = [];

        foreach ($events as $event) {
            $start = EventTime::startsAt($event);

            if (null === $start || $start->isPast()) {
                continue;
            }

            $subEvent = EventSchema::forEvent($event, EventSchema::LISTING_PERFORMER_LIMIT);

            // An instance with no venue of its own inherits the series venue,
            // which is more specific than EventSchema's TBA fallback.
            if (empty($event->venue_id) && $series->venue) {
                $subEvent['location'] = EventSchema::location($series->venue);
            }

            $subEvents[] = $subEvent;
        }

        return $subEvents;
    }
}
