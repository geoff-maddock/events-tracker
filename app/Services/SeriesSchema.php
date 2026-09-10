<?php

namespace App\Services;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Series;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Maps a Series onto a schema.org EventSeries node.
 *
 * EventSeries is a subtype of Event, and Search Console validates it as one:
 * the series pages were the source of a block of "missing endDate / offers /
 * eventStatus / performer" warnings because the node carried only a name,
 * url, description, image and location. This emits the same recommended
 * property set EventSchema does for a single event, drawing dates from the
 * next instantiated event (or the recurrence schedule when none exists yet)
 * and everything else from the series row and its promoter, venue and
 * lineup. Shared pieces — the Place, Organization and PerformingGroup
 * shapes, URL validation — come from EventSchema so the two never drift.
 */
class SeriesSchema
{
    /** Performers emitted per series; lineups on a series are a curated handful. */
    public const PERFORMER_LIMIT = 10;

    /**
     * Upcoming instances emitted as subEvents. The busiest series in the data
     * runs 18 events in a year, so this never truncates a real schedule — it
     * bounds the page if a festival ever announces a very long run at once.
     * The template this replaced was bounded by the archive's pagination.
     */
    public const SUB_EVENT_LIMIT = 25;

    /** Occurrence types whose schedule can be projected to a next date. */
    private const SCHEDULED_TYPES = ['Weekly', 'Biweekly', 'Monthly', 'Bimonthly', 'Yearly'];

    /**
     * A standalone EventSeries document for the series page, with its
     * upcoming instances as subEvents.
     *
     * @param  iterable<int, Event>  $upcomingEvents
     * @return array<string, mixed>
     */
    public static function document(Series $series, iterable $upcomingEvents = []): array
    {
        $node = ['@context' => EventSchema::CONTEXT] + self::forSeries($series);

        $subEvents = [];
        foreach ($upcomingEvents as $event) {
            if (count($subEvents) >= self::SUB_EVENT_LIMIT) {
                break;
            }

            $subEvents[] = self::subEvent($series, $event);
        }

        if (!empty($subEvents)) {
            $node['subEvent'] = $subEvents;
        }

        return $node;
    }

    /**
     * The EventSeries node with no @context, for embedding in an ItemList.
     *
     * Relations read: photos, venue.locations, venue.links, venue.photos,
     * promoter.links, entities.roles, entities.links, upcomingEvent,
     * occurrenceType. Callers rendering many series should eager load them.
     *
     * @return array<string, mixed>
     */
    public static function forSeries(Series $series): array
    {
        $url = route('series.show', $series->slug);

        $node = [
            '@type'            => 'EventSeries',
            '@id'              => $url.'#series',
            'name'             => $series->name,
            'url'              => $url,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
        ];

        [$startDate, $endDate] = self::dates($series);

        if ($startDate) {
            $node['startDate'] = $startDate;
        }

        if ($endDate) {
            $node['endDate'] = $endDate;
        }

        $node['eventAttendanceMode'] = EventSchema::CONTEXT.'/OfflineEventAttendanceMode';
        $node['eventStatus'] = EventSchema::CONTEXT.($series->cancelled_at ? '/EventCancelled' : '/EventScheduled');
        $node['image'] = [self::image($series)];
        $node['description'] = self::description($series);
        $node['location'] = EventSchema::location($series->venue);
        $node['offers'] = self::offers($series, $url);
        $node['performer'] = self::performers($series);

        if ($organizer = $series->promoter ?: $series->venue) {
            $node['organizer'] = EventSchema::organization($organizer);
        }

        return $node;
    }

    /**
     * An instance of the series as an embedded Event. An instance with no
     * venue of its own inherits the series venue — with its address — which
     * is more specific than EventSchema's TBA fallback.
     *
     * @return array<string, mixed>
     */
    public static function subEvent(Series $series, Event $event): array
    {
        $node = EventSchema::forEvent($event, EventSchema::LISTING_PERFORMER_LIMIT);

        if (empty($event->venue_id) && $series->venue) {
            $node['location'] = EventSchema::location($series->venue);
        }

        return $node;
    }

    /**
     * The next instantiated event's times when there is one; otherwise the
     * series' own start/end wall-clock times on the next date its recurrence
     * schedule lands on. A cancelled series, one with no schedule, or one
     * whose schedule cannot be projected gets no dates rather than wrong ones.
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected static function dates(Series $series): array
    {
        if ($next = $series->nextEvent()) {
            return [
                EventTime::startsAt($next)?->toAtomString(),
                EventTime::endsAt($next)?->toAtomString(),
            ];
        }

        if ($series->cancelled_at || !$series->start_at) {
            return [null, null];
        }

        if (!in_array($series->occurrenceType?->name, self::SCHEDULED_TYPES, true)) {
            return [null, null];
        }

        try {
            // Series::cycleFromFoundedAt() walks the schedule forward from
            // founded_at; nthOfMonth() can hand back false for an impossible
            // week/day pair, which Carbon::parse() then chokes on.
            $date = $series->nextOccurrenceDate();
        } catch (Throwable) {
            return [null, null];
        }

        if (!$date) {
            return [null, null];
        }

        $start = EventTime::toInstant($date->format('Y-m-d').' '.$series->start_at->format('H:i:s'));

        if (!$start) {
            return [null, null];
        }

        $minutes = Event::DEFAULT_LENGTH * 60;

        if ($series->end_at) {
            // Wall-clock difference; an end before the start means it
            // crosses midnight.
            $minutes = (int) $series->start_at->diffInMinutes($series->end_at, false);
            if ($minutes <= 0) {
                $minutes += 24 * 60;
            }
        }

        return [$start->toAtomString(), $start->addMinutes($minutes)->toAtomString()];
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
     * Short blurb, else long description, else a sentence built from the
     * schedule and venue — never absent.
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

        if ($type = $series->occurrenceType?->name) {
            if (in_array($type, self::SCHEDULED_TYPES, true)) {
                $sentence .= ', a '.strtolower($type).' series';
            }
        }

        if ($series->venue) {
            $sentence .= ' at '.$series->venue->name;
        }

        return $sentence.'.';
    }

    /**
     * Same rules as EventSchema::offers(): only a real absolute URL, and a
     * price only when one is on file.
     *
     * @return array<string, mixed>
     */
    protected static function offers(Series $series, string $seriesUrl): array
    {
        $offer = [
            '@type'        => 'Offer',
            'url'          => EventSchema::validUrl($series->ticket_link) ?? EventSchema::validUrl($series->primary_link) ?? $seriesUrl,
            'availability' => EventSchema::CONTEXT.'/InStock',
        ];

        $price = $series->door_price ?? $series->presale_price;

        if (null !== $price && '' !== $price) {
            $offer['price'] = (string) $price;
            $offer['priceCurrency'] = 'USD';
        }

        if ($validFrom = EventTime::toInstant($series->created_at)) {
            $offer['validFrom'] = $validFrom->toAtomString();
        }

        return $offer;
    }

    /**
     * The series' dj/band/producer entities, falling back to the series
     * itself so the property is never empty.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function performers(Series $series): array
    {
        $performers = self::performerEntities($series)
            ->take(self::PERFORMER_LIMIT)
            ->map(fn (Entity $entity) => EventSchema::performer($entity))
            ->values()
            ->all();

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
     * @return Collection<int, Entity>
     */
    protected static function performerEntities(Series $series): Collection
    {
        return $series->entities
            ->filter(fn (Entity $entity) => $entity->roles->whereIn('slug', ['dj', 'band', 'producer'])->isNotEmpty())
            ->sortBy('name')
            ->values();
    }
}
