<?php

namespace App\Models;

use App\Models\Concerns\RecommendedByFollows;
use App\Models\Concerns\RefersToEntities;
use App\Contracts\HasPhotos;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Database\Eloquent\Builder;
use App\Filters\SeriesFilters;
use App\Services\OptionLists;

/**
 * App\Models\Series.
 *
 * @property int                                                           $id
 * @property string                                                        $name
 * @property string                                                        $slug
 * @property string|null                                                   $short
 * @property string|null                                                   $description
 * @property int|null                                                      $visibility_id
 * @property int|null                                                      $event_type_id
 * @property int|null                                                      $occurrence_type_id
 * @property int|null                                                      $occurrence_week_id
 * @property int|null                                                      $occurrence_day_id
 * @property int                                                           $hold_date
 * @property \Illuminate\Support\Carbon|null                               $created_at
 * @property \Illuminate\Support\Carbon|null                               $updated_at
 * @property int                                                           $is_benefit
 * @property int|null                                                      $promoter_id
 * @property int|null                                                      $venue_id
 * @property int                                                           $attending
 * @property int                                                           $like
 * @property string|null                                                   $presale_price
 * @property string|null                                                   $door_price
 * @property string|null                                                   $primary_link
 * @property string|null                                                   $ticket_link
 * @property \Illuminate\Support\Carbon|null                               $founded_at
 * @property \Illuminate\Support\Carbon|null                               $cancelled_at
 * @property string|null                                                 $facebook_username
 * @property string|null                                                 $instagram_username
 * @property string|null                                                 $twitter_username
 * @property \Illuminate\Support\Carbon|null                               $soundcheck_at
 * @property \Illuminate\Support\Carbon|null                               $door_at
 * @property \Illuminate\Support\Carbon|null                               $start_at
 * @property \Illuminate\Support\Carbon|null                               $end_at
 * @property int|null                                                      $length
 * @property int|null                                                      $min_age
 * @property int|null                                                      $created_by
 * @property int|null                                                      $updated_by
 * @property User|null                                                     $creator
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Entity[] $entities
 * @property int|null                                                      $entities_count
 * @property \App\Models\EventType|null                                    $eventType
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Event[]  $events
 * @property int|null                                                      $events_count
 * @property mixed                                                         $end_time
 * @property mixed                                                         $entity_list
 * @property string                                                        $occurrence_repeat
 * @property mixed                                                         $tag_list
 * @property \App\Models\OccurrenceDay|null                                $occurrenceDay
 * @property \App\Models\OccurrenceType|null                               $occurrenceType
 * @property \App\Models\OccurrenceWeek|null                               $occurrenceWeek
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Photo[]  $photos
 * @property int|null                                                      $photos_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[]    $tags
 * @property int|null                                                      $tags_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Thread[] $threads
 * @property int|null                                                      $threads_count
 * @property User|null                                                     $user
 * @property \App\Models\Entity|null                                       $venue
 * @property \App\Models\Visibility|null                                   $visibility
 * @property int|null                                                      $attendees_count
 * @property int|null                                                      $popularity_score
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Series active()
 * @method static \Illuminate\Database\Eloquent\Builder|Series future()
 * @method static \Illuminate\Database\Eloquent\Builder|Series newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Series newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Series past()
 * @method static \Illuminate\Database\Eloquent\Builder|Series query()
 * @method static \Illuminate\Database\Eloquent\Builder|Series starting($date)
 * @method static \Illuminate\Database\Eloquent\Builder|Series visible($user)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereAttending($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereDoorAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereDoorPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereEventTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereFoundedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereHoldDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereIsBenefit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereLength($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereLike($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereMinAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereOccurrenceDayId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereOccurrenceTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereOccurrenceWeekId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series wherePresalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series wherePrimaryLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series wherePromoterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereShort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereSoundcheckAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereTicketLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereVenueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Series whereVisibilityId($value)
 * @mixin \Eloquent
 */
class Series extends Eloquent implements HasPhotos
{
    use \App\Models\Concerns\Followable;
    use \App\Models\Concerns\OwnsPhotos;
    use \App\Models\Concerns\BlankToNull;
    use \App\Models\Concerns\HasVisibility;
    use HasFactory;
    use RecommendedByFollows;
    use RefersToEntities;

    /** An empty form field is stored as null (BlankToNull). */
    protected const BLANK_TO_NULL_DATES = ['founded_at', 'soundcheck_at', 'door_at', 'start_at', 'end_at', 'cancelled_at'];
    protected const BLANK_TO_NULL_INTS = ['occurrence_week_id', 'occurrence_day_id', 'promoter_id', 'venue_id', 'length'];

    protected $attributes = [
        'hold_date' => false,
    ];

    /**
     * hold_date is NOT NULL: store a missing value as false, so an API PUT that
     * omits it (and resets optional fields to null) can't fail the update.
     */
    public function setHoldDateAttribute(mixed $value): void
    {
        $this->attributes['hold_date'] = (bool) $value;
    }

    /**
     * Relations series/card-tw reads for each card; list pages eager-load these
     * so a page of cards costs a fixed number of queries (#2173).
     *
     * @var array<int, string>
     */
    public const CARD_EAGER_LOAD = [
        'visibility', 'occurrenceType', 'tags', 'entities', 'photos', 'upcomingEvent', 'upcomingPublicEvent', 'venue.locations.visibility',
    ];

    public static function boot()
    {
        parent::boot();

        // keep the cached series dropdown options (event/thread forms) in sync
        static::saved(fn () => OptionLists::refreshSeries());
        static::deleted(fn () => OptionLists::refreshSeries());
        // Discord targets filtering on it (#2240)
        static::deleted(fn (Series $series) => DiscordTargetCriterion::forgetSubject($series));
    }

    protected $with = ['occurrenceType', 'occurrenceWeek', 'occurrenceDay'];

    protected $fillable = [
        'name',
        'slug',
        'short',
        'description',
        'event_type_id',
        'occurrence_type_id',
        'occurrence_week_id',
        'occurrence_day_id',
        'promoter_id',
        'venue_id',
        'presale_price',
        'door_price',
        'soundcheck_at',
        'founded_at',
        'cancelled_at',
        'door_at',
        'start_at',
        'end_at',
        'length',
        'min_age',
        'hold_date',
        'visibility_id',
        'primary_link',
        'ticket_link',
        'created_by',
        'facebook_username',
        'instagram_username',
        'twitter_username',
    ];


    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generated URLs always use the lower-case slug; legacy mixed-case slugs
     * still resolve because the slug column collation is case-insensitive.
     */
    public function getRouteKey(): string
    {
        return strtolower($this->slug);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return is_numeric($value)
            ? $this->where('id', $value)->firstOrFail()
            : $this->where('slug', $value)->firstOrFail();
    }

    /**
     * Additional fields to treat as Carbon instances.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'founded_at' => 'datetime',
        'soundcheck_at' => 'datetime',
        'door_at' => 'datetime',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    /** @var array{string, Carbon}|null the remembered result of cycleFromFoundedAt(), with what it was worked out from */
    private ?array $nextCycleDate = null;

     /**
     * @param Builder<Event> $query 
     */
    public function scopeFuture(Builder $query): Builder
    {
        return $query->where('start_at', '>=', Carbon::now())
                        ->orderBy('start_at', 'asc');
    }

    /**
     * @param Builder<Event> $query 
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_at', '<', Carbon::now())
                        ->orderBy('start_at', 'desc');
    }

    /**
     * @param Builder<Event> $query 
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('visibility_id', '!=', Visibility::VISIBILITY_CANCELLED);
    }

    /**
     * Returns event series that start on the specified date.
     */
    public function scopeStarting(Builder $query, string | Date $date): Builder
    {
        $cdate = Carbon::parse($date);
        $cdate_yesterday = Carbon::parse($date)->subDay();
        $cdate_tomorrow = Carbon::parse($date)->addDay();

        return $query->where('start_at', '>', $cdate_yesterday->toDateString().' 23:59:59')
                    ->where('start_at', '<', $cdate_tomorrow->toDateString().' 00:00:00');
    }

    /**
     * @param Builder<static>  $match
     * @param array<int, int> $seriesIds
     */
    protected function orInFollowedSeries(Builder $match, array $seriesIds): void
    {
        $match->orWhereIn('series.id', $seriesIds);
    }

    /**
     * Get the end time of the event.
     */
    public function getEndTimeAttribute(): ?DateTime
    {
        if (isset($this->end_at)) {
            return $this->end_at;
        } else {
            return $this->start_at->addDay()->startOfDay();
        }
    }

    /**
     * Get the formatted age requirement for the series.
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
     * Return a collection of series with the passed tag.
     * @return Builder<Series>
     **/
    public static function getByTag(string $tag): Builder
    {
        // get a list of series that have the passed tag
        return self::whereHas('tags', function ($q) use ($tag) {
            $q->where('slug', '=', $tag);
        });
    }

    /**
     * Return a collection of series with the passed venue.
     * @return Builder<Series>
     **/
    public static function getByVenue(string $slug): Builder
    {
        // get a list of series that have the passed tag
        return self::whereHas('venue', function ($q) use ($slug) {
            $q->where('slug', '=', $slug);
        });
    }

    /**
     * Return a collection of series with the passed entity.
     * @return Builder<Series>
     **/
    public static function getByEntity(string $slug): Builder
    {
        // get a list of events that have the passed entity
        return self::whereHas('entities', function ($q) use ($slug) {
            $q->where('slug', '=', $slug);
        });
    }

    /**
     * Get the threads that belong to the series.
     *
     * @return BelongsToMany<Thread, $this>
     */
    public function threads(): BelongsToMany
    {
        return $this->belongsToMany(Thread::class)->orderBy('created_at', 'DESC')->withTimestamps();
    }

    /**
     * An series can have many events.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class)->orderBy('start_at', 'DESC');
    }

    /**
     * An event is owned by a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * An event is created by one user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get length of the event in hours.
     */
    public function length(): float
    {
        if ($this->start_at) {
            // (int) keeps Carbon 2's truncated-integer semantics after the
            // Carbon 3 upgrade, where diffIn* returns fractional floats
            return (int) $this->start_at->diffInHours($this->end_time, false);
        }

        return 0;
    }

    /**
     * An event is created by one user.
     */
    public function ownedBy(User $user): bool
    {
        return $this->created_by == $user->id;
    }

    /**
     * An event has one promoter.
     *
     * @return HasOne<Entity, $this>
     */
    public function promoter(): HasOne
    {
        return $this->hasOne(Entity::class, 'id', 'promoter_id');
    }

    /**
     * An event has one venue.
     *
     * @return HasOne<Entity, $this>
     */
    public function venue(): HasOne
    {
        return $this->hasOne(Entity::class, 'id', 'venue_id');
    }

    /**
     * An series  has one type.
     */
    public function eventType(): HasOne
    {
        return $this->hasOne(EventType::class, 'id', 'event_type_id');
    }

    /**
     * An event has one status.
     */
    public function eventStatus(): HasOne
    {
        return $this->hasOne(EventStatus::class, 'id', 'event_status_id');
    }

    /**
     * An series has one occurrence type.
     */
    public function occurrenceType(): HasOne
    {
        return $this->hasOne(OccurrenceType::class, 'id', 'occurrence_type_id');
    }

    /**
     * An series has one occurrence week.
     */
    public function occurrenceWeek(): HasOne
    {
        return $this->hasOne(OccurrenceWeek::class, 'id', 'occurrence_week_id');
    }

    /**
     * An series has one occurrence day.
     */
    public function occurrenceDay(): HasOne
    {
        return $this->hasOne(OccurrenceDay::class, 'id', 'occurrence_day_id');
    }

    /**
     * Returns when the occurrence repeats.
     */
    public function getOccurrenceRepeatAttribute(): string
    {
        $repeat = '';

        $week = $this->occurrenceWeek ? $this->occurrenceWeek->name : '';
        $day = $this->occurrenceDay ? $this->occurrenceDay->name.'s' : '';

        // Nullable: series.occurrence_type_id is nullable and a one-off
        // series has none. null matches no case and leaves $repeat empty.
        switch ($this->occurrenceType?->name) {
            case 'Monthly':
            case 'Bimonthly':
                $repeat = $week.' '.$day;
                break;
            case 'Weekly':
            case 'Biweekly':
                $repeat = $day;
                break;
            case 'Yearly':
                // Yearly series don't repeat on a week/day cadence; the SEO
                // title (getSeoTitleFormat()) doesn't use this value for
                // Yearly series at all, but callers that do concatenate it
                // (e.g. getTitleFormat()) must trim() the result so an empty
                // string here doesn't leave a dangling space.
                $repeat = '';
                break;
        }

        return $repeat;
    }

    /**
     * Returns the next instantiated event related to this series.
     */
    public function nextEvent(): ?Event
    {
        if ($this->cancelled_at) {
            return null;
        }

        // Use eager loaded relationship if available
        if ($this->relationLoaded('upcomingEvent')) {
            return $this->upcomingEvent;
        }

        return Event::where('series_id', '=', $this->id)
                ->where('start_at', '>=', Carbon::now())
                ->orderBy('start_at', 'asc')
                ->first();
    }

    /**
     * The next upcoming event.
     *
     * @return HasOne<Event, $this>
     */
    public function upcomingEvent(): HasOne
    {
        // ofMany, so eager loading a page of series fetches one event per series
        // instead of every future event of every series (#2173)
        return $this->hasOne(Event::class)->ofMany(
            ['start_at' => 'min', 'id' => 'min'],
            fn ($query) => $query->where('start_at', '>=', Carbon::now())
        );
    }

    /**
     * The next upcoming event anyone may see, for display (series cards, the
     * series page, the API, JSON-LD). nextEvent()/upcomingEvent count every
     * instance, which is what scheduling and "already has an event" checks need.
     */
    public function nextPublicEvent(): ?Event
    {
        if ($this->cancelled_at) {
            return null;
        }

        if ($this->relationLoaded('upcomingPublicEvent')) {
            return $this->upcomingPublicEvent;
        }

        return Event::where('series_id', '=', $this->id)
            ->where('start_at', '>=', Carbon::now())
            ->public()
            ->orderBy('start_at', 'asc')
            ->first();
    }

    /**
     * The next upcoming public event, eager-loadable (see nextPublicEvent()).
     *
     * @return HasOne<Event, $this>
     */
    public function upcomingPublicEvent(): HasOne
    {
        return $this->hasOne(Event::class)->ofMany(
            ['start_at' => 'min', 'id' => 'min'],
            fn ($query) => $query->where('start_at', '>=', Carbon::now())
                ->where('events.visibility_id', Visibility::VISIBILITY_PUBLIC)
        );
    }

    /**
     * The most recent instance that has already happened. Read by
     * App\Services\SeriesSchema when a dormant series has no upcoming
     * instance to date its EventSeries node from.
     *
     * @return HasOne<Event, $this>
     */
    public function latestEvent(): HasOne
    {
        return $this->hasOne(Event::class)->ofMany(
            ['start_at' => 'max', 'id' => 'max'],
            fn ($query) => $query->where('start_at', '<', Carbon::now())
                ->where('events.visibility_id', Visibility::VISIBILITY_PUBLIC)
        );
    }

    /**
     * Get all series that would fall on the passed date.
     *
     * @param Collection<int, Event>|null $events
     *
     * @return array<int, Series>
     */
    public static function byNextDate(?string $date, ?Collection $events = null): array
    {
        // series that already have an event listed on this day are shown with that event
        $ids = $events ? $events->pluck('series_id')->filter()->all() : [];

        $list = [];
        foreach (self::publicWithNextDates() as $row) {
            $series = $row['series'];
            $occurrenceDate = $row['date'];
            $beforeCancellation = null === $series->cancelled_at
                || (null !== $occurrenceDate && $occurrenceDate < $series->cancelled_at->format('Y-m-d'));

            if ($occurrenceDate === $date && $beforeCancellation && !in_array($series->id, $ids)) {
                $list[] = $series;
            }
        }

        return $list;
    }

    /**
     * byNextDate() for each of $days days from $firstDay, keyed by day (Y-m-d),
     * for the day views. $eventsByDay is Event::cardsByDay() for the same
     * window: a series with an event listed on a day is left out of that day.
     *
     * @param Collection<string, Collection<int, Event>>|null $eventsByDay
     *
     * @return Collection<string, array<int, Series>>
     */
    public static function byDay(\DateTimeInterface|string $firstDay, int $days, ?Collection $eventsByDay = null): Collection
    {
        $first = Carbon::parse($firstDay)->startOfDay();
        $byDay = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $first->copy()->addDays($offset)->format('Y-m-d');
            $byDay[$day] = self::byNextDate($day, $eventsByDay?->get($day));
        }

        return collect($byDay);
    }

    /**
     * Every active, public, scheduled series with its next occurrence date (Y-m-d).
     *
     * The next date only depends on today, so this is built once per request: the home
     * page shows 4 days and /events/week 7, and each used to reload every series with
     * its relations and walk its occurrence cycle again (#2168).
     *
     * @return array<int, array{series: Series, date: string|null}>
     */
    public static function publicWithNextDates(): array
    {
        return once(function (): array {
            $rows = [];

            $series = Series::active()
                ->with([
                    'visibility',
                    'occurrenceType',
                    'occurrenceWeek',
                    'occurrenceDay',
                    'venue',
                    'entities',
                    'tags',
                    'photos',
                    // nextEvent() and the card's nextPublicEvent() use these when loaded
                    'upcomingEvent',
                    'upcomingPublicEvent',
                ])
                ->get();

            foreach ($series as $s) {
                if ('Public' === $s->visibility?->name && 'No Schedule' !== $s->occurrenceType?->name) {
                    $rows[] = ['series' => $s, 'date' => $s->nextOccurrenceDate()?->format('Y-m-d')];
                }
            }

            return $rows;
        });
    }

    /**
     * Returns the date of the next occurrence of this template.
     */
    public function nextOccurrenceDate(): ?Carbon
    {
        $next = $this->cycleFromFoundedAt();

        return null === $next || $this->isCancelledBy($next) ? null : $next;
    }

    /**
     * Whether the series has stopped by this date. A cancellation takes effect on
     * its own day: the occurrence that day is off, and so is every later one.
     */
    public function isCancelledBy(\DateTimeInterface $date): bool
    {
        return null !== $this->cancelled_at && $date->format('Y-m-d') >= $this->cancelled_at->format('Y-m-d');
    }

    /**
     * Returns the date of the next occurence, either actual or scheduled
     */
    public function nextPlannedStartAt(): ?Carbon
    {
        if ($this->nextEvent()) {
            return $this->nextEvent()->start_at;
        }

        // a series with no schedule, or a cancelled one, has no next date to work out
        if ('No Schedule' === $this->occurrenceType?->name || null !== $this->cancelled_at) {
            return null;
        }

        return $this->cycleFromFoundedAt();
    }


    /**
     * Returns the end date time of the next occurrence of this template.
     */
    public function nextOccurrenceEndDate(): ?Carbon
    {
        return $this->nextOccurrenceDate()?->addHours($this->length);
    }

    /** Schedules that happen every second period, and so need to know which one is "on". */
    private const TWO_PERIOD_TYPES = ['Biweekly', 'Bimonthly'];

    /** @var array{int, int|null, Carbon|null}|null the remembered result of latestEventDay(): the day and series it was read for */
    private ?array $latestEventDay = null;

    /**
     * The next date on this series' schedule, today or later: the founding date (or
     * the created date) moved forward a period at a time until it reaches today in
     * New York.
     *
     * A Biweekly or Bimonthly series takes its "on" week or month from its latest
     * event, when it has one, so it follows the rhythm it has actually been
     * keeping; the weekday and the time of day still come from the series (#2422).
     *
     * Remembered per model for as long as the day and the schedule stay the same.
     * Each call gets its own copy, since callers add to the date they are given.
     */
    public function cycleFromFoundedAt(): ?Carbon
    {
        $today = Carbon::now('America/New_York')->startOfDay();
        $lastHeld = $this->latestEventDay($today);

        $key = implode('|', [
            $today->getTimestamp(),
            $this->attributes['founded_at'] ?? '',
            $this->attributes['created_at'] ?? '',
            $this->occurrence_week_id,
            $this->occurrence_day_id,
            $this->occurrenceType?->name,
            $lastHeld?->getTimestamp(),
        ]);

        if ($this->nextCycleDate === null || $this->nextCycleDate[0] !== $key) {
            $this->nextCycleDate = [$key, $this->cycleFromFoundedAtTo($today, $lastHeld)];
        }

        return $this->nextCycleDate[1]->copy();
    }

    /**
     * For a two-period schedule, the day of the series' latest event before
     * today that wasn't cancelled, at midnight in the series' own timezone.
     * Null for the other schedules and for a series with no such event, which
     * count from the founding date. Today's event is left to nextEvent().
     *
     * One query per model and day: lists and digests ask for the next date of
     * each series several times over.
     */
    private function latestEventDay(Carbon $today): ?Carbon
    {
        if (!$this->exists || !in_array($this->occurrenceType?->name, self::TWO_PERIOD_TYPES, true)) {
            return null;
        }

        if ($this->latestEventDay === null || $this->latestEventDay[0] !== $today->getTimestamp() || $this->latestEventDay[1] !== $this->id) {
            // events are stored as wall-clock times: compare on the date, not the instant
            $latest = Event::query()
                ->where('series_id', $this->id)
                ->where('start_at', '<', $today->format('Y-m-d').' 00:00:00')
                ->whereNull('cancelled_at')
                ->where(fn ($status) => $status->whereNull('event_status_id')->orWhere('event_status_id', '<>', EventStatus::CANCELLED))
                ->max('start_at');

            $this->latestEventDay = [$today->getTimestamp(), $this->id, null === $latest ? null : Carbon::parse($latest)->startOfDay()];
        }

        return $this->latestEventDay[2]?->copy();
    }

    /**
     * The first schedule date on or after $today. $lastHeld, for a two-period
     * schedule, is the day of its latest event: that week or month was "on",
     * so the next one is two after it.
     */
    private function cycleFromFoundedAtTo(Carbon $today, ?Carbon $lastHeld = null): Carbon
    {
        // if no founded date, assume created at date
        $start = $this->founded_at ?: $this->created_at;
        $next = Carbon::parse($start);

        if (null !== $lastHeld || $next < $today) {
            $type = $this->occurrenceType->name;
            $periods = in_array($type, self::TWO_PERIOD_TYPES, true) ? 2 : 1;

            if ('Yearly' === $type) {
                // counted from the founding date each time, so a 29 February founding
                // comes back to the 29th in a leap year (#2422)
                $years = max(0, $today->year - $start->year - 1);
                do {
                    $next = Carbon::parse($start)->addYearsNoOverflow($years++);
                } while ($next < $today);
            } elseif (in_array($type, ['Monthly', 'Bimonthly'], true)) {
                $next = $this->monthlyFrom($next, $lastHeld, $periods, $today);
            } else {
                // weekly, fortnightly and daily steps are a fixed length. A fortnightly
                // series stays on its own weekday: the event only says which week was on.
                if (null !== $lastHeld) {
                    $next = $lastHeld->startOfWeek(Carbon::MONDAY)->addDays($next->dayOfWeekIso - 1)->addDays(14);
                }

                // stop one short and let the loop below take the last step
                $days = match ($type) {
                    'Weekly' => 7,
                    'Biweekly' => 14,
                    default => 1,
                };
                $skipped = intdiv($today->getTimestamp() - $next->getTimestamp(), $days * 86400) - 1;

                if ($skipped > 0) {
                    $next = Carbon::parse($next)->addDays($skipped * $days);
                }

                while ($next < $today) {
                    $next = $this->cycleForward($next);
                }
            }
        }

        $next->setHour($start->hour);
        $next->setMinute($start->minute);

        return $next;
    }

    /**
     * The first monthly (or two-monthly) date on or after $today. The schedule
     * starts the period after the founding month, or after the month of the
     * latest event; from there it is worked out for the month wanted, not
     * stepped to, so a short month is never skipped or leaned on (#2422).
     */
    private function monthlyFrom(Carbon $start, ?Carbon $lastHeld, int $periods, Carbon $today): Carbon
    {
        $base = $lastHeld ?? $start;
        $here = Carbon::parse($today)->setTimezone($start->getTimezone());

        // whole periods from the base month to today's month, never fewer than one
        $ahead = ($here->year - $base->year) * 12 + $here->month - $base->month;
        $steps = max(1, intdiv($ahead, $periods));

        do {
            $next = $this->monthlyDate($base, $steps++ * $periods);
        } while ($next < $today);

        return $next;
    }

    /**
     * The schedule's date $months after the month of $base: its named day (the
     * second Tuesday, the last Friday), or without one, the same day of the
     * month as $base, or the month's last day where it has no such day.
     */
    private function monthlyDate(Carbon $base, int $months): Carbon
    {
        if (!$this->hasMonthlyDay()) {
            return $base->copy()->addMonthsNoOverflow($months);
        }

        $month = $base->copy()->startOfMonth()->addMonths($months);
        $weekday = $this->occurrence_day_id - 1;

        return 5 === (int) $this->occurrence_week_id
            ? $month->lastOfMonth($weekday)
            : $month->nthOfMonth($this->occurrence_week_id, $weekday);
    }

    /**
     * Whether a monthly schedule names its day: the first to fourth, or last,
     * of a weekday.
     */
    private function hasMonthlyDay(): bool
    {
        return in_array((int) $this->occurrence_week_id, [1, 2, 3, 4, 5], true)
            && in_array((int) $this->occurrence_day_id, [1, 2, 3, 4, 5, 6, 7], true);
    }

    /**
     * The occurrence after $date: one period on, or two for Biweekly and Bimonthly.
     */
    public function cycleForward(?DateTime $date): Carbon
    {
        $carbonDate = Carbon::parse($date);
        $type = $this->occurrenceType->name;
        $periods = in_array($type, self::TWO_PERIOD_TYPES, true) ? 2 : 1;

        return match ($type) {
            'Yearly' => $carbonDate->addYearNoOverflow(),
            // by the month, not by adding to the day: a month added to the 29th-31st
            // can run past a shorter month and skip it (#2422)
            'Monthly', 'Bimonthly' => $this->monthlyDate($carbonDate, $periods),
            'Weekly', 'Biweekly' => $carbonDate->addWeeks($periods),
            default => $carbonDate->addDay(),
        };
    }

    /**
     * Returns the most recent event.
     */
    public function lastEvent(): ?Event
    {
        return $this->events()->past()->first();
    }

    /**
     * An event has one visibility.
     */
    public function visibility(): HasOne
    {
        return $this->hasOne(Visibility::class, 'id', 'visibility_id');
    }

    /**
     * The tags that belong to the event.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * The entities that belong to the event.
     *
     * @return BelongsToMany<Entity, $this>
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * Get a list of tag ids associated with the event.
     */
    public function getTagListAttribute(): array
    {
        return $this->tags->pluck('id')->all();
    }

    /**
     * Get a list of tag names associated with the series.
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

    public function getTitleFormat(): string
    {
        $format = $this->name;

        if ($this->occurrenceType != null) {
            $format .= ' - '.$this->occurrenceType->name.' '.$this->occurrence_repeat;
        }

        // include the location of the event
        if ($this->venueLabel()) {
            $format .= ' at ';
            $format .= $this->venueLabel();
        }

        return $format;
    }

    /**
     * Whether this series reads as a festival for SEO purposes: an explicit
     * Festival event type, or a Yearly occurrence (covers annual events —
     * e.g. "Skull Fest" — that were never tagged with the Festival event
     * type but only ever happen once a year).
     */
    public function isFestival(): bool
    {
        if ($this->eventType && 'festival' === $this->eventType->slug) {
            return true;
        }

        return (bool) ($this->occurrenceType && 'Yearly' === $this->occurrenceType->name);
    }

    /**
     * The year to surface in a festival title/heading: the next upcoming
     * event's year, else the most recent past event's year, else null when
     * the series has no events at all.
     */
    public function getFestivalYear(): ?int
    {
        if ($next = $this->nextEvent()) {
            return $next->start_at->year;
        }

        if ($last = $this->lastEvent()) {
            return $last->start_at->year;
        }

        return null;
    }

    /**
     * SEO-friendly title (does NOT include the site brand — the layout
     * appends that). Festival series get "{name} {year} — Lineup,
     * Schedule & Tickets in Pittsburgh" with the year omitted cleanly when
     * unknown. Non-festival series fall back to "{name} — {OccurrenceType}
     * {occurrence_repeat} at {venue}", with each clause dropped cleanly
     * (no dangling separators) when its data is missing. Kept separate
     * from getTitleFormat(), which other callers still use unchanged.
     */
    public function getSeoTitleFormat(): string
    {
        if ($this->isFestival()) {
            $year = $this->getFestivalYear();

            return $this->name.($year ? ' '.$year : '').' — Lineup, Schedule & Tickets in Pittsburgh';
        }

        $format = $this->name;
        $clauses = [];

        if ($this->occurrenceType && 'No Schedule' !== $this->occurrenceType->name) {
            $occurrenceClause = trim($this->occurrenceType->name.' '.$this->occurrence_repeat);
            if ('' !== $occurrenceClause) {
                $clauses[] = $occurrenceClause;
            }
        }

        if ($this->venueLabel()) {
            $clauses[] = 'at '.$this->venueLabel();
        }

        if (!empty($clauses)) {
            $format .= ' — '.implode(' ', $clauses);
        }

        return $format;
    }

    /**
     * Get the click-tracking URL for the series' ticket link
     * 
     * @return string|null
     */
    public function getTicketTrackingLink(): ?string
    {
        if (!$this->ticket_link) {
            return null;
        }

        return route('clicktrack.series', ['id' => $this->id]);
    }

    /**
     * Apply SeriesFilters to the query.
     */
    public function scopeFilter(Builder $query, SeriesFilters $filters): Builder
    {
        return $filters->apply($query);
    }
}
