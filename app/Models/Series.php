<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

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
 * @property \Illuminate\Support\Carbon                                    $created_at
 * @property \Illuminate\Support\Carbon                                    $updated_at
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
class Series extends Eloquent
{
    use HasFactory;

    protected $attributes = [
        'hold_date' => false,
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($series) {
            $user = Auth::user();
            $series->created_by = $user ? $user->id : 1;
            $series->updated_by = $user ? $user->id : 1;
        });

        static::updating(function ($series) {
            $user = Auth::user();
            $series->updated_by = $user ? $user->id : 1;
        });
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
        'benefit_id',
        'promoter_id',
        'venue_id',
        'location_id',
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
    ];

    /**
     * Additional fields to treat as Carbon instances.
     *
     * @var array
     */
    protected $dates = ['founded_at', 'cancelled_at', 'soundcheck_at', 'door_at', 'start_at', 'end_at'];

    public function scopeFuture(Builder $query): Builder
    {
        return $query->where('start_at', '>=', Carbon::now())
                        ->orderBy('start_at', 'asc');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_at', '<', Carbon::now())
                        ->orderBy('start_at', 'desc');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
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
     * Returns visible events.
     */
    public function scopeVisible(Builder $query, ?User $user): Builder
    {
        return $query->where(function ($query) use ($user) {
            $query->whereIn('visibility_id', [Visibility::VISIBILITY_PROPOSAL, Visibility::VISIBILITY_PRIVATE])
                ->where('created_by', '=', $user ? $user->id : null);
            // if logged in, can see guarded
            if ($user) {
                $query->orWhere('visibility_id', '=', Visibility::VISIBILITY_GUARDED);
            }
            $query->orWhere('visibility_id', '=', Visibility::VISIBILITY_PUBLIC);

            return $query;
        });
    }

    /**
     * Set the occurrence week attribute.
     */
    public function setOccurrenceWeekIdAttribute(?int $value): void
    {
        if (!empty($value)) {
            $this->attributes['occurrence_week_id'] = $value;
        } else {
            $this->attributes['occurrence_week_id'] = null;
        }
    }

    /**
     * Set the occurrence day attribute.
     */
    public function setOccurrenceDayIdAttribute(?int $value): void
    {
        if (!empty($value)) {
            $this->attributes['occurrence_day_id'] = $value;
        } else {
            $this->attributes['occurrence_day_id'] = null;
        }
    }

    /**
     * Set the promoter attribute.
     */
    public function setPromoterIdAttribute(?int $value): void
    {
        if (!empty($value)) {
            $this->attributes['promoter_id'] = $value;
        } else {
            $this->attributes['promoter_id'] = null;
        }
    }

    /**
     * Set the venue attribute.
     */
    public function setVenueIdAttribute(?int $value): void
    {
        if (!empty($value)) {
            $this->attributes['venue_id'] = $value;
        } else {
            $this->attributes['venue_id'] = null;
        }
    }

    /**
     * Set the length attribute.
     */
    public function setLengthAttribute(?int $value): void
    {
        if (!empty($value)) {
            $this->attributes['length'] = $value;
        } else {
            $this->attributes['length'] = null;
        }
    }

    /**
     * Set the founded_at attribute.
     */
    public function setFoundedAtAttribute(?string $date): void
    {
        if (!empty($date)) {
            $this->attributes['founded_at'] = Carbon::parse($date);
        } else {
            $this->attributes['founded_at'] = null;
        }
    }

    /**
     * Set the cancelled_at attribute.
     */
    public function setCancelledAtAttribute(?string $date): void
    {
        if (!empty($date)) {
            $this->attributes['cancelled_at'] = Carbon::parse($date);
        } else {
            $this->attributes['cancelled_at'] = null;
        }
    }

    /**
     * Set the soundcheck_at attribute.
     */
    public function setSoundcheckAtAttribute(?string $date): void
    {
        if (!empty($date)) {
            $this->attributes['soundcheck_at'] = Carbon::parse($date);
        } else {
            $this->attributes['soundcheck_at'] = null;
        }
    }

    /**
     * Set the start_at attribute.
     */
    public function setStartAtAttribute(?string $date): void
    {
        if (!empty($date)) {
            $this->attributes['start_at'] = Carbon::parse($date);
        } else {
            $this->attributes['start_at'] = null;
        }
    }

    /**
     * Set the end_at attribute.
     */
    public function setEndAtAttribute(?string $date): void
    {
        if (!empty($date)) {
            $this->attributes['end_at'] = Carbon::parse($date);
        } else {
            $this->attributes['end_at'] = null;
        }
    }

    /**
     * Set the door_at attribute.
     */
    public function setDoorAtAttribute(?string $date): void
    {
        if (!empty($date)) {
            $this->attributes['door_at'] = Carbon::parse($date);
        } else {
            $this->attributes['door_at'] = null;
        }
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
     * Return a collection of series with the passed tag.
     *
     **/
    public static function getByTag(string $tag): Builder
    {
        // get a list of series that have the passed tag
        return self::whereHas('tags', function ($q) use ($tag) {
            $q->where('slug', '=', $tag);
        });
    }

    /**
     * Return a collection of series with the passed event type.
     *
     **/
    public static function getByType(string $slug): Builder
    {
        // get a list of series that have the passed tag
        return self::whereHas('eventType', function ($q) use ($slug) {
            $q->where('name', '=', $slug);
        });
    }

    /**
     * Return a collection of series with the passed venue.
     *
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
     *
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
            return $this->start_at->diffInHours($this->end_time, false);
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
     * Get all of the series photos.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class)->withTimestamps();
    }

    /**
     * An event has one promoter.
     */
    public function promoter(): HasOne
    {
        return $this->hasOne(Entity::class, 'id', 'promoter_id');
    }

    /**
     * An event has one venue.
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

        switch ($this->occurrenceType->name) {
            case 'Monthly':
            case 'Bimonthly':
                $repeat = $week.' '.$day;
                break;
            case 'Weekly':
            case 'Biweekly':
                $repeat = $day;
                break;
        }

        return $repeat;
    }

    /**
     * Returns the date of the next occurrence of this event.
     */
    public function nextEvent(): ?Event
    {
        $event = null;

        if (null == $this->cancelled_at) {
            $event = Event::where('series_id', '=', $this->id)->where('start_at', '>=', Carbon::now())
                        ->orderBy('start_at', 'asc')->first();
        }

        return $event;
    }

    /**
     * Get all series that would fall on the passed date.
     */
    public static function byNextDate(?string $date): array
    {
        $list = [];

        // get all the upcoming series events
        $series = Series::active()->with(['visibility', 'occurrenceType'])->get();

        $series = $series->filter(function ($e) {
            // all public events
            // and all events with a schedule
            //$next_date = $e->nextOccurrenceDate()->format('Y-m-d');

            return ('Public' === $e->visibility->name) and ('No Schedule' !== $e->occurrenceType->name);
        });

        foreach ($series as $s) {
            if (null == $s->nextEvent() and null != $s->nextOccurrenceDate()) {
                // add matches to list
                $next_date = $s->nextOccurrenceDate()->format('Y-m-d');

                if ($next_date == $date) {
                    $list[] = $s;
                }
            }
        }

        return $list;
    }

    /**
     * Returns the date of the next occurrence of this template.
     */
    public function nextOccurrenceDate(): ?Carbon
    {
        return $this->cycleFromFoundedAt();
    }

    /**
     * Returns the end date time of the next occurrence of this template.
     */
    public function nextOccurrenceEndDate(): ?Carbon
    {
        return $this->nextOccurrenceDate()->addHours($this->length);
    }

    /**
     * Cycles forward from the founding date to the most recent date.
     */
    public function cycleFromFoundedAt(): ?Carbon
    {
        // local founded at
        $next = $this->founded_at;

        // if no founded date, assume created at date
        if (!$next) {
            $next = $this->created_at;
        }

        while ($next < Carbon::now('America/New_York')->startOfDay()) {
            $next = $this->cycleForward($next);
        }

        $next->setHour($this->founded_at->hour);
        $next->setMinute($this->founded_at->minute);

        return $next;
    }

    /**
     * Returns the date of the next occurrence of this template.
     */
    public function cycleForward(?DateTime $date): Carbon
    {
        $carbonDate = Carbon::parse($date);

        switch ($this->occurrenceType->name) {
            case 'Yearly':
                $next = $carbonDate->addYear();
                break;
            case 'Monthly':
            case 'Bimonthly':
                $next = $carbonDate->addMonth();
                if ($date) {
                    $next = $carbonDate->nthOfMonth($this->occurrence_week_id, ($this->occurrence_day_id - 1));
                } else {
                    $next = $carbonDate->addMonth()->startOfMonth();
                }

                break;
            case 'Weekly':
            case 'Biweekly':
                $next = $carbonDate->addWeek();
                break;
            default:
                $next = $carbonDate->addDay();
        }

        return $next;
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

    public function addPhoto(Photo $photo): void
    {
        $this->photos()->attach($photo->id);
    }

    /**
     * Return the primary photo for this event.
     *
     **/
    public function getPrimaryPhoto(): ?Photo
    {
        // get a list of events that start on the passed date
        return $this->photos()->where('photos.is_primary', '=', '1')->first();
    }

    /**
     * Checks if the series is followed by the user.
     *
     **/
    public function followedBy(User $user): ?Follow
    {
        return Follow::where('object_type', '=', 'series')
        ->where('object_id', '=', $this->id)
        ->where('user_id', '=', $user->id)
        ->first();
    }

    /**
     * Returns the users that follow the series.
     *
     **/
    public function followers(): Collection
    {
        return User::join('follows', 'users.id', '=', 'follows.user_id')
            ->where('follows.object_type', 'series')
            ->where('follows.object_id', $this->id)
            ->get('users.*');
    }

    /**
     * The follows that belong to the entity.
     */
    public function follows(): MorphMany
    {
        return $this->morphMany(Follow::class, 'object', 'object_type', 'object_id');
    }

    /**
     * Returns the format for a calendar entry.
     */
    public function getCalendarFormat(): array
    {
        /*
         * [
         *  title,
         *  full day,
         *  start date time, must be a DateTime object or valid DateTime format (http://bit.ly/1z7QWbg)
         *  end date time,
         *  id,
         *  options
         * ]
         */
        return [
            $this->name,
            false,
            $this->nextOccurrenceDate()->format('Y-m-d H:i'),
            ($this->nextOccurrenceEndDate() ? $this->nextOccurrenceEndDate()->format('Y-m-d H:i') : null),
            $this->id,
            [
                'url' => '/series/'.$this->id,
                'color' => '#99bcdb',
            ],
        ];
    }

    public function getTitleFormat(): string
    {
        $format = $this->name;

        if ($this->occurrenceType != null) {
            $format .= ' - '.$this->occurrenceType->name.' '.$this->occurrence_repeat;
        }

        // include the location of the event
        if ($this->venue) {
            $format .= ' at ';
            $format .= $this->venue->name ?? 'No venue specified';
        }

        return $format;
    }
}
