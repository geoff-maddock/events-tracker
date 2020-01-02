<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Series extends Eloquent
{
    public static function boot()
    {
        parent::boot();

        static::creating(function ($series) {
            $series->created_by = Auth::user()->id;
            $series->updated_by = Auth::user() ? Auth::user()->id : null;
        });

        static::updating(function ($series) {
            $series->updated_by = Auth::user() ? Auth::user()->id : null;
        });
    }

    protected $with = ['occurrenceType', 'occurrenceWeek', 'occurrenceDay'];

    /**
     * @var array
     *
     **/
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

    public function scopeFuture($query)
    {
        $query->where('start_at', '>=', Carbon::now())
                        ->orderBy('start_at', 'asc');
    }

    public function scopePast($query)
    {
        $query->where('start_at', '<', Carbon::now())
                        ->orderBy('start_at', 'desc');
    }

    public function scopeActive($query)
    {
        $query->whereNull('cancelled_at');
    }

    /**
     * Returns event series that start on the specified date.
     */
    public function scopeStarting($query, $date)
    {
        $cdate = Carbon::parse($date);
        $cdate_yesterday = Carbon::parse($date)->subDay(1);
        $cdate_tomorrow = Carbon::parse($date)->addDay(1);

        $query->where('start_at', '>', $cdate_yesterday->toDateString().' 23:59:59')
                    ->where('start_at', '<', $cdate_tomorrow->toDateString().' 00:00:00');
    }

    /**
     * Returns visible events.
     */
    public function scopeVisible($query, $user)
    {
        $public = Visibility::where('name', '=', 'Public')->first();

        $query->where('visibility_id', '=', $public ? $public->id : null)->orWhere('created_by', '=', ($user ? $user->id : null));
    }

    /**
     * Set the occurrence week attribute.
     *
     * @param $value
     */
    public function setOccurrenceWeekIdAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['occurrence_week_id'] = $value;
        } else {
            $this->attributes['occurrence_week_id'] = null;
        }
    }

    /**
     * Set the occurrence day attribute.
     *
     * @param $value
     */
    public function setOccurrenceDayIdAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['occurrence_day_id'] = $value;
        } else {
            $this->attributes['occurrence_day_id'] = null;
        }
    }

    /**
     * Set the promoter attribute.
     *
     * @param $value
     */
    public function setPromoterIdAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['promoter_id'] = $value;
        } else {
            $this->attributes['promoter_id'] = null;
        }
    }

    /**
     * Set the venue attribute.
     *
     * @param $value
     */
    public function setVenueIdAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['venue_id'] = $value;
        } else {
            $this->attributes['venue_id'] = null;
        }
    }

    /**
     * Set the length attribute.
     *
     * @param $value
     */
    public function setLengthAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['length'] = $value;
        } else {
            $this->attributes['length'] = null;
        }
    }

    /**
     * Set the founded_at attribute.
     *
     * @param $date
     */
    public function setFoundedAtAttribute($date)
    {
        if (!empty($date)) {
            $this->attributes['founded_at'] = Carbon::parse($date);
        } else {
            $this->attributes['founded_at'] = null;
        }
    }

    /**
     * Set the cancelled_at attribute.
     *
     * @param $date
     */
    public function setCancelledAtAttribute($date)
    {
        if (!empty($date)) {
            $this->attributes['cancelled_at'] = Carbon::parse($date);
        } else {
            $this->attributes['cancelled_at'] = null;
        }
    }

    /**
     * Set the soundcheck_at attribute.
     *
     * @param $date
     */
    public function setSoundcheckAtAttribute($date)
    {
        if (!empty($date)) {
            $this->attributes['soundcheck_at'] = Carbon::parse($date);
        } else {
            $this->attributes['soundcheck_at'] = null;
        }
    }

    /**
     * Set the start_at attribute.
     *
     * @param $date
     */
    public function setStartAtAttribute($date)
    {
        if (!empty($date)) {
            $this->attributes['start_at'] = Carbon::parse($date);
        } else {
            $this->attributes['start_at'] = null;
        }
    }

    /**
     * Set the end_at attribute.
     *
     * @param $date
     */
    public function setEndAtAttribute($date)
    {
        if (!empty($date)) {
            $this->attributes['end_at'] = Carbon::parse($date);
        } else {
            $this->attributes['end_at'] = null;
        }
    }

    /**
     * Set the door_at attribute.
     *
     * @param $date
     */
    public function setDoorAtAttribute($date)
    {
        if (!empty($date)) {
            $this->attributes['door_at'] = Carbon::parse($date);
        } else {
            $this->attributes['door_at'] = null;
        }
    }

    /**
     * Get the end time of the event.
     *
     * @ return
     */
    public function getEndTimeAttribute()
    {
        if (isset($this->end_at)) {
            return $this->end_at;
        } else {
            return  $this->start_at->addDay()->startOfDay();
        }
    }

    /**
     * Return a collection of series with the passed tag.
     *
     * @return Collection $series
     *
     **/
    public static function getByTag($tag)
    {
        // get a list of series that have the passed tag
        $series = self::whereHas('tags', function ($q) use ($tag) {
            $q->where('name', '=', ucfirst($tag));
        });

        return $series;
    }

    /**
     * Return a collection of series with the passed event type.
     *
     * @return Collection $series
     *
     **/
    public static function getByType($slug)
    {
        // get a list of series that have the passed tag
        $series = self::whereHas('eventType', function ($q) use ($slug) {
            $q->where('name', '=', $slug);
        });

        return $series;
    }

    /**
     * Return a collection of series with the passed venue.
     *
     * @return Collection $series
     *
     **/
    public static function getByVenue($slug)
    {
        // get a list of series that have the passed tag
        $series = self::whereHas('venue', function ($q) use ($slug) {
            $q->where('slug', '=', $slug);
        });

        return $series;
    }

    /**
     * Return a collection of series with the passed entity.
     *
     * @return Collection $events
     *
     **/
    public static function getByEntity($slug)
    {
        // get a list of events that have the passed entity
        $series = self::whereHas('entities', function ($q) use ($slug) {
            $q->where('slug', '=', $slug);
        });

        return $series;
    }

    /**
     * Get the threads that belong to the series.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function threads()
    {
        return $this->belongsToMany('App\Thread')->withTimestamps();
    }

    /**
     * An series can have many events.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany('App\Event')->orderBy('start_at', 'DESC');
    }

    /**
     * An event is owned by a user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    /**
     * An event is created by one user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    /**
     * Get length of the event in hours.
     *
     * @ return decimal
     */
    public function length()
    {
        if ($this->start_at) {
            return $this->start_at->diffInHours($this->end_time, false);
        }

        return 0;
    }

    /**
     * An event is created by one user.
     *
     * @ param User $user
     *
     * @ return boolean
     */
    public function ownedBy(User $user)
    {
        return $this->created_by == $user->id;
    }

    /**
     * Get all of the series photos.
     */
    public function photos()
    {
        return $this->belongsToMany('App\Photo')->withTimestamps();
    }

    /**
     * An event has one venue.
     */
    public function venue()
    {
        return $this->hasOne('App\Entity', 'id', 'venue_id');
    }

    /**
     * An series  has one type.
     */
    public function eventType()
    {
        return $this->hasOne('App\EventType', 'id', 'event_type_id');
    }

    /**
     * An series has one occurrence type.
     */
    public function occurrenceType()
    {
        return $this->hasOne(OccurrenceType::class, 'id', 'occurrence_type_id');
    }

    /**
     * An series has one occurrence week.
     */
    public function occurrenceWeek()
    {
        return $this->hasOne(OccurrenceWeek::class, 'id', 'occurrence_week_id');
    }

    /**
     * An series has one occurrence day.
     */
    public function occurrenceDay()
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
    public function nextEvent()
    {
        $event = null;

        if (null !== $this->cancelledAt) {
            $event = Event::where('series_id', '=', $this->id)->where('start_at', '>=', Carbon::now())
                        ->orderBy('start_at', 'asc')->first();
        }

        return $event;
    }

    /**
     * Get all series that would fall on the passed date.
     *
     * @param $date
     *
     * @return mixed
     */
    public static function byNextDate($date)
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
    public function nextOccurrenceDate()
    {
        return $this->cycleFromFoundedAt();
    }

    /**
     * Returns the end date time of the next occurrence of this template.
     */
    public function nextOccurrenceEndDate()
    {
        return $this->nextOccurrenceDate()->addHours($this->length);
    }

    /**
     * Cycles forward from the founding date to the most recent date.
     */
    public function cycleFromFoundedAt()
    {
        // local founded at
        $founded_at = $this->founded_at;

        // if no founded date, assume created at date
        if (!$founded_at) {
            $founded_at = $this->created_at;
        }

        $next = $founded_at;

        if ($next) {  // 10PM
            while ($next < Carbon::now('America/New_York')->startOfDay()) {
                $next = $this->cycleForward($next);
            }
        }

        return $next;
    }

    /**
     * Returns the date of the next occurrence of this template.
     */
    public function cycleForward($date)
    {
        //$days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

        switch ($this->occurrenceType->name) {
            case 'Yearly':
                $next = $date->addYear();
                break;
            case 'Monthly':
            case 'Bimonthly':

                if ($date) {
                    $next = $date->nthOfMonth($this->occurrence_week_id, ($this->occurrence_day_id - 1));
                } else {
                    $next = $date->addMonth()->startOfMonth();
                }

                break;
            case 'Weekly':
            case 'Biweekly':
                $next = $date->addWeek();
                break;
            default:
                $next = $date->addDay();
        }

        return $next;
    }

    /**
     * Returns the most recent event.
     *
     * @return Event | null
     */
    public function lastEvent()
    {
        return $this->events->past()->first();
    }

    /**
     * An event has one visibility.
     */
    public function visibility()
    {
        return $this->hasOne('App\Visibility', 'id', 'visibility_id');
    }

    /**
     * The tags that belong to the event.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany('App\Tag')->withTimestamps();
    }

    /**
     * The entities that belong to the event.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entities()
    {
        return $this->belongsToMany('App\Entity')->withTimestamps();
    }

    /**
     * Get a list of tag ids associated with the event.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getTagListAttribute()
    {
        return $this->tags->pluck('id')->all();
    }

    /**
     * Get a list of entity ids associated with the event.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getEntityListAttribute()
    {
        return $this->entities->pluck('id')->all();
    }

    public function addPhoto(Photo $photo)
    {
        return $this->photos()->attach($photo->id);
    }

    /**
     * Return the primary photo for this event.
     *
     * @return Photo $photo
     *
     **/
    public function getPrimaryPhoto()
    {
        // get a list of events that start on the passed date
        return $this->photos()->where('photos.is_primary', '=', '1')->first();
    }

    /**
     * Checks if the series is followed by the user.
     *
     * @return Follow
     *
     **/
    public function followedBy($user)
    {
        return Follow::where('object_type', '=', 'series')
        ->where('object_id', '=', $this->id)
        ->where('user_id', '=', $user->id)
        ->first();
    }

    /**
     * Returns the users that follow the series.
     *
     * @return Collection
     *
     **/
    public function followers()
    {
        return User::join('follows', 'users.id', '=', 'follows.user_id')
            ->where('follows.object_type', 'series')
            ->where('follows.object_id', $this->id)
            ->get();
    }
}
