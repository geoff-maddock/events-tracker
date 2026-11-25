<?php

namespace App\Models;

use App\Models\Concerns\RecommendedByFollows;
use App\Models\Concerns\RefersToEntities;
use App\Services\EventDateRange;
use App\Services\PriceLabel;
use App\Contracts\HasPhotos;
use App\Filters\QueryFilter;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * App\Models\Event.
 *
 * @property int                                                                                                       $id
 * @property \Carbon\Carbon|null                                                                                      $display_start_at
 * @property \Carbon\Carbon|null                                                                           $last_start_at
 * @property \Illuminate\Database\Eloquent\Collection<int, EventShowing>                                               $showings
 * @property mixed                                                                                                     $created_by
 * @property mixed                                                                                                     $start_at
 * @property Collection                                                                                                $entities
 * @property Collection                                                                                                $tags
 * @property string                                                                                                    $name
 * @property string                                                                                                    $slug
 * @property string|null                                                                                               $short
 * @property string|null                                                                                               $description
 * @property int|null                                                                                                  $visibility_id
 * @property int|null                                                                                                  $event_status_id
 * @property int|null                                                                                                  $event_type_id
 * @property int                                                                                                       $is_benefit
 * @property int|null                                                                                                  $promoter_id
 * @property int|null                                                                                                  $venue_id
 * @property int                                                                                                       $attending
 * @property int                                                                                                       $like
 * @property string|null                                                                                               $presale_price
 * @property string|null                                                                                               $door_price
 * @property \Illuminate\Support\Carbon|null                                                                           $soundcheck_at
 * @property \Illuminate\Support\Carbon|null                                                                           $door_at
 * @property \Illuminate\Support\Carbon|null                                                                           $end_at
 * @property int|null                                                                                                  $min_age
 * @property int|null                                                                                                  $series_id
 * @property string|null                                                                                               $primary_link
 * @property string|null                                                                                               $ticket_link
 * @property int|null                                                                                                  $updated_by
 * @property \Illuminate\Support\Carbon|null                                                                           $created_at
 * @property \Illuminate\Support\Carbon|null                                                                           $updated_at
 * @property \Illuminate\Support\Carbon|null                                                                           $cancelled_at
 * @property bool                                                                                                       $do_not_repost
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[]                                            $comments
 * @property int|null                                                                                                  $comments_count
 * @property \App\Models\User                                                                                          $creator
 * @property int|null                                                                                                  $entities_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\EventResponse[]                                      $eventResponses
 * @property int|null                                                                                                  $event_responses_count
 * @property int|null                                                                                                  $response_count
 * @property \App\Models\EventStatus|null                                                                              $eventStatus
 * @property \App\Models\EventType|null                                                                                $eventType
 * @property mixed                                                                                                     $attending_count
 * @property float                                                                                                     $avg_rating
 * @property int                                                                                                       $count_attended
 * @property int                                                                                                       $count_reviews
 * @property mixed                                                                                                     $end_time
 * @property mixed                                                                                                     $entity_list
 * @property mixed                                                                                                     $length_in_hours
 * @property mixed                                                                                                     $tag_list
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Like[]                                               $likes
 * @property int|null                                                                                                  $likes_count
 * @property \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property int|null                                                                                                  $notifications_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Photo[]                                              $photos
 * @property int|null                                                                                                  $photos_count
 * @property \App\Models\Series|null                                                                                   $series
 * @property int|null                                                                                                  $tags_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Thread[]                                             $threads
 * @property int|null                                                                                                  $threads_count
 * @property \App\Models\User                                                                                          $user
 * @property \App\Models\Entity|null                                                                                   $promoter
 * @property \App\Models\Entity|null                                                                                   $venue
 * @property \App\Models\Visibility|null                                                                               $visibility
 * @property int|null                                                                                                  $popularity_score
 * @mixin \Illuminate\Database\Eloquent\Relations\Relation
 * @method static Builder|Event filter(\App\Filters\QueryFilter $filters)
 * @method static Builder|Event future()
 * @method static Builder|Event newModelQuery()
 * @method static Builder|Event newQuery()
 * @method static Builder|Event past()
 * @method static Builder|Event query()
 * @method static Builder|Event today()
 * @method static Builder|Event visible($user)
 * @method static Builder|Event whereAttending($value)
 * @method static Builder|Event whereCancelledAt($value)
 * @method static Builder|Event whereCreatedAt($value)
 * @method static Builder|Event whereCreatedBy($value)
 * @method static Builder|Event whereDescription($value)
 * @method static Builder|Event whereDoorAt($value)
 * @method static Builder|Event whereDoorPrice($value)
 * @method static Builder|Event whereEndAt($value)
 * @method static Builder|Event whereEventStatusId($value)
 * @method static Builder|Event whereEventTypeId($value)
 * @method static Builder|Event whereId($value)
 * @method static Builder|Event whereIsBenefit($value)
 * @method static Builder|Event whereLike($value)
 * @method static Builder|Event whereMinAge($value)
 * @method static Builder|Event whereName($value)
 * @method static Builder|Event wherePresalePrice($value)
 * @method static Builder|Event wherePrimaryLink($value)
 * @method static Builder|Event wherePromoterId($value)
 * @method static Builder|Event whereSeriesId($value)
 * @method static Builder|Event whereShort($value)
 * @method static Builder|Event whereSlug($value)
 * @method static Builder|Event whereSoundcheckAt($value)
 * @method static Builder|Event whereStartAt($value)
 * @method static Builder|Event whereTicketLink($value)
 * @method static Builder|Event whereUpdatedAt($value)
 * @method static Builder|Event whereUpdatedBy($value)
 * @method static Builder|Event whereVenueId($value)    
 * @method static Builder|Event whereVisibilityId($value)
 * @method static Builder|Event getByEntity($value)
 */
class Event extends Model implements HasPhotos
{
    use \App\Models\Concerns\OwnsPhotos;
    use \App\Models\Concerns\BlankToNull;
    use \App\Models\Concerns\HasVisibility;
    use Notifiable;
    use HasFactory;
    use RecommendedByFollows;
    use RefersToEntities;

    /** An empty form field is stored as null (BlankToNull). */
    protected const BLANK_TO_NULL_DATES = ['soundcheck_at', 'door_at', 'start_at', 'end_at', 'cancelled_at'];
    protected const BLANK_TO_NULL_INTS = ['promoter_id', 'series_id', 'venue_id'];

    const DEFAULT_LENGTH = 4;

    /** The most additional showings one event can carry (#2375). */
    const MAX_SHOWINGS = 60;

    /** Leads cardFingerprint(); bump when the events/card-tw markup changes. */
    const CARD_MARKUP_VERSION = 3;

    public static function boot()
    {
        parent::boot();
    }

    protected static function booted(): void
    {
        // the dated listings 404 outside the cached range of start dates
        static::saved(function (Event $event) {
            if ($event->wasRecentlyCreated || $event->wasChanged('start_at')) {
                EventDateRange::forget();
            }
        });
        static::deleted(fn () => EventDateRange::forget());

        // last_start_at follows start_at until syncShowings() says otherwise (#2375)
        static::saving(function (Event $event) {
            if (!$event->exists) {
                $event->last_start_at = $event->start_at;
            } elseif ($event->isDirty('start_at') || null === $event->last_start_at) {
                $event->last_start_at = $event->lastShowingStart();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'name',
        'slug',
        'short',
        'description',
        'visibility_id',
        'event_status_id',
        'event_type_id',
        'is_benefit',
        'promoter_id',
        'venue_id',
        'presale_price',
        'door_price',
        'ticket_link',
        'primary_link',
        'series_id',
        'soundcheck_at',
        'door_at',
        'start_at',
        'end_at',
        'cancelled_at',
        'do_not_repost',
        'min_age',
        'created_by',
    ];

    protected $casts = [
        'soundcheck_at' => 'datetime',
        'door_at' => 'datetime',
        'start_at' => 'datetime',
        'last_start_at' => 'datetime',
        'end_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'do_not_repost' => 'boolean'
    ];

    /**
     * The additional dates of a short-run event, earliest first (#2375).
     * start_at is the first showing and is not among them.
     *
     * @return HasMany<EventShowing, $this>
     */
    public function showings(): HasMany
    {
        return $this->hasMany(EventShowing::class)->orderBy('start_at');
    }

    /**
     * Whether the event has dates beyond start_at. Read off last_start_at, so
     * it costs no query for the single-date events that make up most lists.
     */
    public function isMultiDate(): bool
    {
        return null !== $this->start_at
            && null !== $this->last_start_at
            && $this->last_start_at->gt($this->start_at);
    }

    /**
     * Every date the event plays, earliest first: start_at as an unsaved
     * showing, then the additional ones.
     *
     * @return Collection<int, EventShowing>
     */
    public function occurrences(): Collection
    {
        $occurrences = collect();

        if (null !== $this->start_at) {
            $first = new EventShowing();
            $first->start_at = $this->start_at;
            $first->end_at = $this->end_at;
            $first->door_at = $this->door_at;
            $occurrences->push($first);
        }

        if ($this->isMultiDate()) {
            $occurrences = $occurrences->concat($this->showings);
        }

        return $occurrences->sortBy(fn (EventShowing $showing) => $showing->start_at->getTimestamp())->values();
    }

    /**
     * The first date that hasn't started yet, or null once the run is over.
     */
    public function nextOccurrence(): ?EventShowing
    {
        $now = Carbon::now();

        return $this->occurrences()->first(fn (EventShowing $showing) => $showing->start_at->gte($now));
    }

    /**
     * The dates that haven't started yet, earliest first.
     *
     * @return Collection<int, EventShowing>
     */
    public function remainingOccurrences(): Collection
    {
        $now = Carbon::now();

        return $this->occurrences()->filter(fn (EventShowing $showing) => $showing->start_at->gte($now))->values();
    }

    /**
     * A short run's remaining dates for a post or caption: by default one line
     * such as "Thu Mar 4 7PM, Sat Mar 6 2:30PM + 3 more". Null for a single-date
     * event, and for a run down to its last date, which the post already names
     * (#2375). Posts list every remaining date, the one they lead with included.
     *
     * @param (callable(EventShowing): string)|null $format one date
     */
    public function showtimesSummary(int $limit = 10, ?callable $format = null, string $glue = ', '): ?string
    {
        if (!$this->isMultiDate()) {
            return null;
        }

        $remaining = $this->remainingOccurrences();
        if ($remaining->count() < 2) {
            return null;
        }

        $format ??= fn (EventShowing $showing): string => $showing->start_at->format('D M j').' '.self::shortTime($showing->start_at);
        $summary = $remaining->take($limit)->map($format)->implode($glue);

        if ($remaining->count() > $limit) {
            // on a line of its own when the dates are one per line
            $summary .= (str_contains($glue, "\n") ? $glue : ' ').'+ '.($remaining->count() - $limit).' more';
        }

        return $summary;
    }

    /**
     * "7PM", or "7:30PM" when the minutes matter.
     */
    public static function shortTime(\DateTimeInterface $time): string
    {
        return '00' === $time->format('i') ? $time->format('gA') : $time->format('g:iA');
    }

    /**
     * Replace the additional showings with $rows ('start_at', and optionally
     * 'end_at' and 'door_at'), and bring last_start_at in line with them.
     * Rows on or before start_at, and repeats of a time, are dropped.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function syncShowings(array $rows): void
    {
        $showings = [];
        foreach ($rows as $row) {
            if (empty($row['start_at'])) {
                continue;
            }

            $start = Carbon::parse($row['start_at']);
            if (null !== $this->start_at && $start->lte($this->start_at)) {
                continue;
            }

            $showings[$start->getTimestamp()] = [
                'start_at' => $start,
                'end_at' => empty($row['end_at']) ? null : Carbon::parse($row['end_at']),
                'door_at' => empty($row['door_at']) ? null : Carbon::parse($row['door_at']),
            ];
        }
        ksort($showings);

        $showings = array_slice(array_values($showings), 0, self::MAX_SHOWINGS);
        $fingerprint = fn (iterable $rows): array => collect($rows)
            ->map(fn ($row) => implode('|', array_map(
                fn ($value) => null === $value ? '' : Carbon::parse($value)->format('Y-m-d H:i:s'),
                [$row['start_at'], $row['end_at'], $row['door_at']]
            )))
            ->values()
            ->all();
        $changed = $fingerprint($this->showings()->get(['start_at', 'end_at', 'door_at'])) !== $fingerprint($showings);

        $this->showings()->delete();
        $this->showings()->createMany($showings);
        $this->unsetRelation('showings');

        $columns = [];
        if ($changed && $this->exists) {
            // a calendar feed's change stamp is the event's updated_at, so moving
            // a showing has to move it too, even when nothing else was edited
            $columns['updated_at'] = $this->freshTimestamp();
        }

        $last = $this->lastShowingStart();
        if ($last?->getTimestamp() !== $this->last_start_at?->getTimestamp()) {
            $columns['last_start_at'] = $last;
        }

        if ([] !== $columns) {
            // not a save(): the event itself didn't change, only these columns
            $this->newQuery()->whereKey($this->getKey())->update($columns);
            foreach ($columns as $column => $value) {
                $this->setAttribute($column, $value);
                $this->syncOriginalAttribute($column);
            }
        }

        if (array_key_exists('last_start_at', $columns)) {
            EventDateRange::forget();
        }
    }

    /**
     * The start of the final showing: the latest additional one, or start_at.
     */
    protected function lastShowingStart(): ?Carbon
    {
        $latest = $this->exists ? $this->showings()->max('start_at') : null;
        $latest = null !== $latest ? Carbon::parse($latest) : null;

        if (null === $this->start_at) {
            return $latest;
        }

        return (null !== $latest && $latest->gt($this->start_at)) ? $latest : $this->start_at->copy();
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return is_numeric($value)
            ? $this->where('id', $value)->firstOrFail()
            : $this->where('slug', $value)->firstOrFail();
    }


    /**
     * @return Builder<Event>
     */
    public function scopeFilter(Builder $query, QueryFilter $filters): Builder
    {
        return $filters->apply($query);
    }

    /**
     * Set the door_price attribute.
     */
    public function setDoorPriceAttribute(mixed $price): void
    {
        // 0 is a real price (free entry); only a blank field means none was entered (#2261)
        $this->attributes['door_price'] = (null === $price || '' === $price) ? null : $price;
    }

    /**
     * Create the slug from the name if none was passed.
     */
    public function setPresalePriceAttribute(mixed $value): void
    {
        // 0 is a real price; only a blank field means none was entered (#2261)
        $this->attributes['presale_price'] = (null === $value || '' === $value) ? null : $value;
    }

    /**
     * The door price as shown: null when none was entered, "Free" for 0.
     */
    public function doorPriceLabel(): ?string
    {
        return PriceLabel::for($this->door_price);
    }

    /**
     * The presale price as shown: null when none was entered, "Free" for 0.
     */
    public function presalePriceLabel(): ?string
    {
        return PriceLabel::for($this->presale_price);
    }

    /**
     * @param Builder<Event> $query
     */
    public function scopeToday(Builder $query): Builder
    {
        /* @var Builder<Event>*/
        $today = Carbon::today();

        return $query->occurringBetween($today, Carbon::tomorrow()->subSecond())->orderByNextStart($today);
    }

    /**
     * Events with a showing inside the given naive datetime bounds
     * (inclusive), ordered by that showing, earliest first. Used by the
     * time-window landing pages (/events/tonight, /this-weekend, /this-week).
     *
     * @param Builder<Event> $query
     */
    public function scopeBetween(Builder $query, string $start, string $end): Builder
    {
        return $query->occurringBetween($start, $end)->orderByNextStart($start);
    }

    /**
     * Events with a date in [$start, $end] (inclusive; either end may be open):
     * start_at itself or, for a short run, any additional showing (#2375).
     *
     * With only $start this is "not over yet"; with only $end, "begun by then".
     * No ordering, so it composes: pair it with orderByNextStart().
     *
     * @param Builder<Event> $query
     */
    public function scopeOccurringBetween(
        Builder $query,
        \DateTimeInterface|string|null $start = null,
        \DateTimeInterface|string|null $end = null
    ): Builder {
        if (null === $start && null === $end) {
            return $query;
        }

        if (null === $end) {
            return $query->where('events.last_start_at', '>=', $start);
        }

        if (null === $start) {
            return $query->where('events.start_at', '<=', $end);
        }

        // Written so the index on last_start_at can narrow it (#2391): a date in
        // the window means the event isn't over at $start, has begun by $end and
        // either starts in the window or, being a run already under way, has a
        // showing in it. Leans on last_start_at never being null or before
        // start_at, which the saving hook and syncShowings() keep true.
        return $query->where(function ($window) use ($start, $end) {
            $window->where('events.last_start_at', '>=', $start)
                ->where('events.start_at', '<=', $end)
                ->where(function ($dated) use ($start, $end) {
                    $dated->where('events.start_at', '>=', $start)
                        ->orWhereExists(function ($showing) use ($start, $end) {
                            $showing->selectRaw('1')
                                ->from('event_showings')
                                ->whereColumn('event_showings.event_id', 'events.id')
                                ->whereBetween('event_showings.start_at', [$start, $end]);
                        });
                });
        });
    }

    /**
     * SQL for the first date of an event on or after a moment: start_at for a
     * single-date event or a run that hasn't begun, otherwise the earliest
     * later showing (start_at again once the run is over). Takes the moment
     * as its two bindings.
     */
    protected static function nextStartSql(): string
    {
        return 'CASE WHEN events.start_at >= ? OR events.last_start_at IS NULL OR events.last_start_at <= events.start_at'
            .' THEN events.start_at'
            .' ELSE COALESCE((SELECT MIN(event_showings.start_at) FROM event_showings'
            .' WHERE event_showings.event_id = events.id AND event_showings.start_at >= ?), events.start_at) END';
    }

    /**
     * Order by each event's first date on or after $from, so a run in
     * progress sorts by its next showing rather than its opening night.
     *
     * @param Builder<Event> $query
     */
    public function scopeOrderByNextStart(Builder $query, \DateTimeInterface|string $from, string $direction = 'asc'): Builder
    {
        $direction = 'desc' === strtolower($direction) ? 'desc' : 'asc';

        // the selected alias when there is one: DISTINCT only orders by selected columns
        if (in_array('next_start_at', self::selectedNames($query), true)) {
            return $query->orderBy('next_start_at', $direction);
        }

        return $query->orderByRaw(self::nextStartSql().' '.$direction, [$from, $from]);
    }

    /**
     * Select each event's first date on or after $from as next_start_at, which
     * display_start_at then shows in place of start_at. Only on a query that
     * returns whole events: a narrowed select (a subquery of ids) is left alone.
     *
     * @param Builder<Event> $query
     */
    public function scopeWithNextStart(Builder $query, \DateTimeInterface|string $from): Builder
    {
        if (null === $query->getQuery()->columns) {
            $query->select('events.*');
        } else {
            $names = self::selectedNames($query);
            $wholeEvents = in_array('events.*', $names, true) || in_array('*', $names, true);

            if (!$wholeEvents || in_array('next_start_at', $names, true)) {
                return $query;
            }
        }

        return $query->selectRaw(self::nextStartSql().' as next_start_at', [$from, $from]);
    }

    /**
     * What a query selects: plain columns as written, and "… as alias" by alias.
     *
     * @param Builder<Event> $query
     *
     * @return array<int, string>
     */
    protected static function selectedNames(Builder $query): array
    {
        $grammar = $query->getQuery()->getGrammar();

        return array_map(function ($column) use ($grammar): string {
            $sql = $column instanceof \Illuminate\Contracts\Database\Query\Expression
                ? (string) $column->getValue($grammar)
                : $column;

            return 1 === preg_match('/\bas\s+`?(\w+)`?\s*$/i', $sql, $match) ? $match[1] : $sql;
        }, $query->getQuery()->columns ?? []);
    }

    /**
     * The date a list shows for the event: the one the query picked
     * (withNextStart), else the next showing still to come, else start_at.
     */
    public function getDisplayStartAtAttribute(): ?Carbon
    {
        if (!empty($this->attributes['next_start_at'])) {
            return Carbon::parse($this->attributes['next_start_at']);
        }

        if (!$this->isMultiDate()) {
            return $this->start_at;
        }

        return $this->nextOccurrence()->start_at ?? $this->start_at;
    }

    /**
     * The event's dates inside [$start, $end], earliest first.
     *
     * @return Collection<int, EventShowing>
     */
    public function occurrencesBetween(\DateTimeInterface $start, \DateTimeInterface $end): Collection
    {
        return $this->occurrences()
            ->filter(fn (EventShowing $showing) => $showing->start_at->between($start, $end))
            ->values();
    }

    /**
     * Group events under each day in [$start, $end] they play, keyed Y-m-d and
     * ordered by time. A short run appears under every one of its days, as a
     * copy whose display_start_at is that day's showing.
     *
     * @param iterable<Event> $events
     *
     * @return Collection<string, Collection<int, Event>>
     */
    public static function groupByDay(iterable $events, \DateTimeInterface $start, \DateTimeInterface $end): Collection
    {
        $days = [];

        foreach ($events as $event) {
            foreach ($event->occurrencesBetween($start, $end) as $showing) {
                $shown = $event;
                if ($event->isMultiDate()) {
                    // never saved: the extra attribute only steers display_start_at
                    $shown = clone $event;
                    $shown->setAttribute('next_start_at', $showing->start_at->format('Y-m-d H:i:s'));
                }

                $days[$showing->start_at->format('Y-m-d')][] = $shown;
            }
        }

        return collect($days)->map(
            fn (array $day) => collect($day)->sortBy(fn (Event $event) => $event->display_start_at?->getTimestamp())->values()
        );
    }

    /**
     * @param Builder<Event> $query 
     */
    public function scopeFuture(Builder $query): Builder
    {
        // a short run stays until its last showing, placed by its next one (#2375)
        $today = Carbon::today()->startOfDay();

        return $query->occurringBetween($today)->orderByNextStart($today);
    }

    /**
     * Events in one of the user's followed series; through the series table, so a
     * follow of a deleted series (whose events keep its id) matches nothing.
     *
     * @param Builder<static>  $match
     * @param array<int, int> $seriesIds
     */
    protected function orInFollowedSeries(Builder $match, array $seriesIds): void
    {
        $match->orWhereHas('series', fn ($q) => $q->whereIn('series.id', $seriesIds));
    }

    /**
     * @param Builder<Event> $query
     */
    public function scopePast(Builder $query): Builder
    {
        // over only once the last showing has passed
        return $query->where('events.last_start_at', '<', Carbon::today()->startOfDay())
            ->orderBy('events.start_at', 'desc');
    }

    /**
     * The cards the day views show for each of $days days from $firstDay: what
     * $user may see (the visible() rule, as on /events), keyed by day (Y-m-d)
     * and in time order. One query for the whole window; a short run appears
     * under each of its days, with that day's time (#2375). A day with nothing
     * on has no key.
     *
     * @return Collection<string, Collection<int, Event>>
     */
    public static function cardsByDay(\DateTimeInterface|string $firstDay, int $days, ?User $user): Collection
    {
        $start = Carbon::parse($firstDay)->startOfDay();
        $end = $start->copy()->addDays($days - 1)->endOfDay();

        $events = self::with([
            'showings',
            'series.occurrenceType',
            'series.occurrenceWeek',
            'series.occurrenceDay',
            'series.visibility',
            'visibility',
            'venue',
            'entities',
            'tags',
            'threads',
            // only the viewer's response is shown on the card
            'eventResponses' => fn ($responses) => $responses->where('user_id', $user?->id),
            'photos',
        ])
            ->occurringBetween($start, $end)
            ->visible($user)
            ->orderByNextStart($start)
            ->get();

        return self::groupByDay($events, $start, $end);
    }

    /**
     * Get all of the events comments.
     *
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany('App\Models\Comment', 'commentable')->orderBy('created_at', 'DESC');
    }

    /**
     * The likes that belong to the event.
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Like')->withTimestamps();
    }

    /**
     * An event is owned by a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * An event is created by one user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * An event is created by one user.
     */
    public function ownedBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    /**
     * An event has one promoter.
     *
     * @return BelongsTo<Entity, $this>
     */
    public function promoter(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'promoter_id');
    }

    /**
     * An event has one venue.
     *
     * @return BelongsTo<Entity, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'venue_id');
    }

    /**
     * An event has one type.
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    /**
     * An event has one status.
     */
    public function eventStatus(): BelongsTo
    {
        return $this->belongsTo(EventStatus::class, 'event_status_id');
    }

    /**
     * An event can belong to many threads.
     *
     * @return HasMany<Thread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany('App\Models\Thread');
    }

    /**
     * An event has one visibility.
     */
    public function visibility(): HasOne
    {
        return $this->hasOne(Visibility::class, 'id', 'visibility_id');
    }

    /**
     * An event has one series.
     *
     * @return BelongsTo<Series, $this>
     */
    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class, 'series_id');
    }

    /**
     * The tags that belong to the event.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Tag')->withTimestamps();
    }

    /**
     * The entities that belong to the event.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Entity')->withTimestamps();
    }

    /**
     * A user can have many event responses.
     */
    public function eventResponses(): HasMany
    {
        return $this->hasMany('App\Models\EventResponse');
    }
    /**
     * Users marked as attending this event.
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, "event_responses")
            ->wherePivot("response_type_id", ResponseType::ATTENDING);
    }


    /**
     * Get the count of users attending this event.
     */
    public function getAttendingCountAttribute(): int
    {
        // the filter() result used to be discarded, so this counted every response type
        return $this->eventResponses()->where('response_type_id', ResponseType::ATTENDING)->count();
    }

    /**
     * Get past or future attribute.
     */
    public function getPastOrFutureAttribute(): string
    {
        if ($this->start_at < Carbon::now()) {
            return 'event-past';
        } else {
            return 'event-future';
        }
    }

    /**
     * Get the length of the event in hours.
     */
    public function getLengthInHoursAttribute(): float
    {
        if ($this->start_at) {
            // (int) keeps Carbon 2's truncated-integer semantics after the
            // Carbon 3 upgrade, where diffIn* returns fractional floats
            return (int) $this->start_at->diffInHours($this->end_time, false);
        }

        return 0;
    }

    /**
     * Get the end time of the event.
     */
    public function getEndTimeAttribute(): ?Carbon
    {
        if (isset($this->end_at)) {
            return $this->end_at;
        }

        return null;
    }

    /**
     * Get the formatted age requirement for the event.
     * Returns "All Ages" for 0, or age with "+" suffix for non-zero values.
     */
    public function getAgeFormatAttribute(): string
    {
        if (is_null($this->min_age)) {
            return '';
        }

        if ($this->min_age == 0) {
            return 'All Ages';
        }

        return $this->min_age.'+';
    }

    /**
     * Get the default end time of the event.  If no end time is set, use the default length to determine end time.
     */
    public function getDefaultEndTimeAttribute(): ?Carbon
    {
        if (isset($this->end_at)) {
            return $this->end_at;
        }

        if (isset($this->start_at)) {
            return $this->start_at->addHours(self::DEFAULT_LENGTH);
        }

        return null;
    }


    /**
     * Get a list of tag ids associated with the event.
     */
    public function getTagListAttribute(): array
    {
        return $this->tags->pluck('id')->all();
    }

    /**
     * Get a list of tag names associated with the event.
     */
    public function getTagNamesAttribute(): ?string
    {
        return implode(', ', $this->tags->pluck('name')->all());
    }

    /**
     * Get a list of entity ids associated with the event.
     */
    public function getEntityListAttribute(): array
    {
        return $this->entities->pluck('id')->all();
    }

    /**
     * Return a collection of events with the passed tag.
     * @return Builder<Event>
     */
    public static function getByTag(string $tag): Builder
    {
        // get a list of events that have the passed tag
        return self::whereHas('tags', function (Builder $q) use ($tag) {
            $q->where('slug', '=', $tag);
        });
    }

    /**
     * Return a collection of events with the passed venue.
     * @return Builder<Event>
     */
    public static function getByVenue(string $slug): Builder
    {
        // get a list of events that have the passed tag
        return self::whereHas('venue', function (Builder $q) use ($slug) {
            $q->where('slug', '=', $slug);
        });
    }

    /**
     * Return a collection of events with the passed series.
     * @return Builder<Event>
     */
    public static function getBySeries(?string $slug): Builder
    {
        // get a list of events that have the passed tag
        return self::whereHas('series', function ($q) use ($slug) {
            $q->where('slug', '=', $slug);
        });
    }

    /**
     * Return a collection of events with the passed entity.
     * @return Builder<Event>
     */
    public static function getByEntity(?string $slug): Builder
    {
        // get a list of events that have the passed entity
        return self::whereHas('entities', function ($q) use ($slug) {
            $q->where('slug', '=', $slug);
        });
    }

    /**
     * Get any related performer type entities.
     * Uses in-memory filtering when entities and roles are already loaded.
     */
    public function performerEntities(?int $rpp = null): LengthAwarePaginator | Collection
    {
        if ($this->relationLoaded('entities') && $this->entities->every(fn ($e) => $e->relationLoaded('roles'))) {
            $filtered = $this->entities->filter(
                fn ($entity) => $entity->roles->whereIn('slug', ['dj', 'band', 'producer'])->isNotEmpty()
            )->sortBy('name');

            return $rpp !== null ? $filtered->take($rpp)->values() : $filtered->values();
        }

        return $this->entities()->whereHas('roles', function ($q) {
            $q->whereIn('slug', ['dj', 'band', 'producer']);
        })->orderBy('name', 'ASC')->paginate($rpp);
    }

    /**
     * Returns the response status for the passed user.
     **/
    public function getEventResponse(User $user): ?EventResponse
    {
        // check if the relation is loaded
        if ($this->relationLoaded('eventResponses')) {
            return $this->eventResponses->where('user_id', $user->id)->first();
        }
        
        return EventResponse::where('event_id', '=', $this->id)
            ->where('user_id', '=', $user->id)->first();
    }

    public function canUserPostPhoto(User $user): bool
    {
        $response = EventResponse::where('event_id', '=', $this->id)
        ->where('user_id', '=', $user->id)->first();

        if ($this->start_at < Carbon::now() && $response) {
            return true;
        }

        return false;
    }

    /**
     * Returns a count of reviews.
     *
     * @return int
     *
     **/
    public function getCountReviewsAttribute()
    {
        return EventReview::where('event_id', '=', $this->id)
            ->count();
    }

    /**
     * Returns a count of confirmed attendees.
     *
     * @return int
     *
     **/
    public function getCountAttendedAttribute()
    {
        return EventReview::where('event_id', '=', $this->id)
            ->where('attended', '=', 1)
            ->count();
    }

    /**
     * Returns an average of reviews.
     *
     * @return float
     *
     **/
    public function getAvgRatingAttribute()
    {
        return EventReview::where('event_id', '=', $this->id)
            ->avg('rating');
    }

    /**
     * Build a cache fingerprint for the event-card partials.
     *
     * Combines the event's own updated_at with the freshness of the related data
     * the card renders (photos, tags, related entities, venue) so editing any of
     * them busts the cached fragment — including changes that do NOT touch the
     * event's own timestamp, e.g. attaching a photo via the BelongsToMany pivot.
     * Only already-loaded relations are inspected, so this adds no queries when
     * the caller has eager-loaded them (the entity show controller does).
     *
     * CARD_MARKUP_VERSION leads the key: bump it when events/card-tw changes
     * structure, or guests keep getting the old cached markup for up to 6 hours.
     */
    public function cardFingerprint(): string
    {
        $stamp = function (string $relation): string {
            if (!$this->relationLoaded($relation)) {
                return '-';
            }
            $related = $this->getRelation($relation);
            if ($related instanceof \Illuminate\Support\Collection) {
                return ($related->max('updated_at')?->getTimestamp() ?? '0').':'.$related->count();
            }

            // belongsTo (venue) — single model or null
            return (string) ($related?->updated_at?->getTimestamp() ?? '0');
        };

        return implode('|', [
            'v'.self::CARD_MARKUP_VERSION,
            $this->id,
            $this->updated_at?->getTimestamp() ?? '0',
            'ph'.$stamp('photos'),
            'tg'.$stamp('tags'),
            'en'.$stamp('entities'),
            've'.$stamp('venue'),
            // a run's card shows its next showing, which moves on as dates pass
            'ds'.($this->isMultiDate() ? $this->display_start_at?->getTimestamp() : ''),
        ]);
    }

    /**
     * Return the primary photo for this event.
     *
     **/
    public function getOtherPhotos(): Collection
    {
        // gets any other photos related to this event
        return $this->photos()->where('photos.is_primary', '=', '0')->get();
    }

    /**
     * Create the slug from the name if none was passed.
     */
    public function setSlugAttribute(?string $value): void
    {
        // grab the title and slugify it
        if ('' === $value) {
            $value = Str::slug($this->name);
        }

        // slugs starting with a digit are ambiguous with id-based lookups
        if (is_string($value) && '' !== $value && ctype_digit($value[0])) {
            $value = '-'.$value;
        }

        $this->attributes['slug'] = $value;
    }

    /**
     * An "add to Google Calendar" link for one date of the event, from
     * occurrences(): by default start_at, the date the page leads with (#2375).
     */
    public function getGoogleCalendarLink(?EventShowing $occurrence = null): ?string
    {
        // the opening date carries the event's own end_at, so every link reads the same way
        $occurrence ??= $this->occurrences()->first();
        $startAt = $occurrence->start_at ?? $this->start_at;
        $start = Carbon::parse($startAt)->format('Ymd\THis');
        // the date's own end when it has one; otherwise a zero-length slot, as before
        $end = Carbon::parse($occurrence->end_at ?? $startAt)->format('Ymd\THis');

        // name, description and venue are user text, so every value is URL-encoded;
        // dates is only digits/T and Google expects a literal slash between them
        $query = http_build_query([
            'action' => 'TEMPLATE',
            'text' => $this->name,
            'details' => $this->description,
            'location' => $this->venueLabel() ?? 'Unknown',
            'sf' => 'true',
            'output' => 'xml',
        ], '', '&', PHP_QUERY_RFC3986);

        // TODO get this URL from config
        return 'https://www.google.com/calendar/render?'.$query.'&dates='.$start.'/'.$end;
    }

    /**
     * Get the click-tracking URL for the event's ticket link
     * 
     * @return string|null
     */
    public function getTicketTrackingLink(): ?string
    {
        if (!$this->ticket_link) {
            return null;
        }

        return route('clicktrack.event', ['id' => $this->id]);
    }

    public function getDateLastTitleFormat(): ?string
    {
        $format = $this->name;

        // include the location of the event
        if ($this->venueLabel()) {
            $format .= ' at ';
            $format .= $this->venueLabel();
        }

        // a short run: its first and last dates (#2375), with years across New Year
        if ($this->isMultiDate() && null !== $this->last_start_at) {
            $dateFormat = $this->start_at->year === $this->last_start_at->year ? 'D F jS' : 'D F jS Y';
            $format .= ' from '.$this->start_at->format($dateFormat).' to '.$this->last_start_at->format($dateFormat);
        } else {
            $format .= ' on '.$this->start_at->format('D F jS');
        }

        return $format;
    }

    public function getTitleFormat(): ?string
    {
        $format = $this->start_at->format('l F jS Y').' '.$this->name;

        // include the location of the event
        if ($this->venueLabel()) {
            $format .= ' at ';
            $format .= $this->venueLabel();
        }

        return $format;
    }
}
