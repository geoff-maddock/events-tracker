<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use DateTime;

/**
 * App\Models\Event
 *
 * @property int $id
 * @property mixed $created_by
 * @property mixed $start_at
 * @property Collection $entities
 * @property Collection $tags
 * @property string $name
 * @property string $slug
 * @property string|null $short
 * @property string|null $description
 * @property int|null $visibility_id
 * @property int|null $event_status_id
 * @property int|null $event_type_id
 * @property int $is_benefit
 * @property int|null $promoter_id
 * @property int|null $venue_id
 * @property int $attending
 * @property int $like
 * @property string|null $presale_price
 * @property string|null $door_price
 * @property \Illuminate\Support\Carbon|null $soundcheck_at
 * @property \Illuminate\Support\Carbon|null $door_at
 * @property \Illuminate\Support\Carbon|null $end_at
 * @property int|null $min_age
 * @property int|null $series_id
 * @property string|null $primary_link
 * @property string|null $ticket_link
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $comments
 * @property-read int|null $comments_count
 * @property-read \App\Models\User $creator
 * @property-read int|null $entities_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EventResponse[] $eventResponses
 * @property-read int|null $event_responses_count
 * @property-read \App\Models\EventStatus|null $eventStatus
 * @property-read \App\Models\EventType|null $eventType
 * @property-read mixed $attending_count
 * @property-read float $avg_rating
 * @property-read int $count_attended
 * @property-read int $count_reviews
 * @property-read mixed $end_time
 * @property-read mixed $entity_list
 * @property-read mixed $length_in_hours
 * @property-read mixed $tag_list
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Like[] $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Photo[] $photos
 * @property-read int|null $photos_count
 * @property-read \App\Models\Series|null $series
 * @property-read int|null $tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Thread[] $threads
 * @property-read int|null $threads_count
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Entity|null $venue
 * @property-read \App\Models\Visibility|null $visibility
 * @method static Builder|Event filter(\App\Filters\QueryFilter $filters)
 * @method static Builder|Event future()
 * @method static Builder|Event newModelQuery()
 * @method static Builder|Event newQuery()
 * @method static Builder|Event past()
 * @method static Builder|Event query()
 * @method static Builder|Event starting($date)
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
 */
class Event extends Eloquent
{
    use Notifiable;
    use HasFactory;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            $user = Auth::user();
            $event->created_by = $user ? $user->id : null;
            $event->updated_by = $user ? $user->id : null;
        });

        static::updating(function ($event) {
            $user = Auth::user();
            $event->updated_by = $user ? $user->id : null;
        });
    }

    /**
     * @var array
     *
     **/
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
        'min_age',
        'created_by',
    ];

    protected $dates = ['soundcheck_at', 'door_at', 'start_at', 'end_at', 'cancelled_at'];

    public function scopeFilter(Builder $query, QueryFilter $filters): Builder
    {
        return $filters->apply($query);
    }

    /**
     * Set the soundcheck_at attribute.
     *
     * @param ?string $date
     */
    public function setSoundcheckAtAttribute(?string $date)
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
     * @param ?string $date
     */
    public function setStartAtAttribute(?string $date)
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
     * @param ?string $date
     */
    public function setEndAtAttribute(?string $date)
    {
        if (!empty($date)) {
            $this->attributes['end_at'] = Carbon::parse($date);
        } else {
            $this->attributes['end_at'] = null;
        }
    }

    /**
     * Set the cancelled at attribute.
     *
     * @param ?string $date
     */
    public function setCancelledAtAttribute(?string $date)
    {
        if (!empty($date)) {
            $this->attributes['cancelled_at'] = Carbon::parse($date);
        } else {
            $this->attributes['cancelled_at'] = null;
        }
    }

    /**
     * Set the door_at attribute.
     *
     * @param ?string $date
     */
    public function setDoorAtAttribute(?string $date)
    {
        if (!empty($date)) {
            $this->attributes['door_at'] = Carbon::parse($date);
        } else {
            $this->attributes['door_at'] = null;
        }
    }

    /**
     * Set the door_price attribute.
     *
     * @param ?string $price
     */
    public function setDoorPriceAttribute(?string $price)
    {
        if (!empty($price)) {
            $this->attributes['door_price'] = $price;
        } else {
            $this->attributes['door_price'] = null;
        }
    }

    /**
     * Set the promoter attribute.
     *
     * @param ?int $value
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
     * Set the series attribute.
     *
     * @param ?int $value
     */
    public function setSeriesIdAttribute(?int $value): void
    {
        if (!empty($value)) {
            $this->attributes['series_id'] = $value;
        } else {
            $this->attributes['series_id'] = null;
        }
    }

    /**
     * Set the venue attribute.
     *
     * @param ?int $value
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
     * Create the slug from the name if none was passed.
     */
    public function setPresalePriceAttribute(?float $value)
    {
        if (!empty($value)) {
            $this->attributes['presale_price'] = $value;
        } else {
            $this->attributes['presale_price'] = null;
        }
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('start_at', '=', Carbon::today()->toDateString())
            ->orderBy('start_at', 'asc');
    }

    public function scopeFuture(Builder $query): Builder
    {
        return $query->where('start_at', '>=', Carbon::today()->startOfDay())
            ->orderBy('start_at', 'asc');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_at', '<', Carbon::today()->startOfDay())
            ->orderBy('start_at', 'desc');
    }

    /**
     * Returns visible events.
     *
     * @param Builder $query
     * @param User    $user
     */
    public function scopeVisible($query, $user)
    {
        $public = Visibility::where('name', '=', 'Public')->first();

        $query->where('visibility_id', '=', $public ? $public->id : null)->orWhere('created_by', '=', ($user ? $user->id : null));
    }

    /**
     * Returns visible events.
     *
     */
    public function scopeStarting(BUilder $query, ?string $date)
    {
        $cdate_yesterday = Carbon::parse($date)->subDay();
        $cdate_tomorrow = Carbon::parse($date)->addDay();

        $query->where('start_at', '>', $cdate_yesterday->toDateString() . ' 23:59:59')
            ->where('start_at', '<', $cdate_tomorrow->toDateString() . ' 00:00:00')
            ->where(function ($query) {
                return $query->where('visibility_id', '=', 3)
                    ->orWhere('created_by', '=', Auth::user() ? Auth::user()->id : null);
            })
            ->orderBy('start_at', 'ASC')
            ->with('visibility');
    }

    /**
     * Get all of the events comments.
     */
    public function comments()
    {
        return $this->morphMany('App\Models\Comment', 'commentable')->orderBy('created_at', 'DESC');
    }

    /**
     * The likes that belong to the event.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likes()
    {
        return $this->belongsToMany('App\Models\Like')->withTimestamps();
    }

    /**
     * An event is owned by a user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * An event is created by one user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * An event is created by one user.
     *
     * @ param User $user
     *
     * @ return boolean
     */
    public function ownedBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    /**
     * An event has one venue.
     */
    public function venue(): HasOne
    {
        return $this->hasOne('App\Models\Entity', 'id', 'venue_id');
    }

    /**
     * An event has one type.
     */
    public function eventType(): HasOne
    {
        return $this->hasOne('App\Models\EventType', 'id', 'event_type_id');
    }

    /**
     * An event has one status.
     */
    public function eventStatus(): HasOne
    {
        return $this->hasOne('App\Models\EventStatus', 'id', 'event_status_id');
    }

    /**
     * Get all of the events photos.
     */
    public function photos()
    {
        return $this->belongsToMany('App\Models\Photo')->withTimestamps();
    }

    /**
     * An event can belong to many threads.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function threads()
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
     */
    public function series(): HasOne
    {
        return $this->hasOne(Series::class, 'id', 'series_id');
    }

    /**
     * The tags that belong to the event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Tag')->withTimestamps();
    }

    /**
     * The entities that belong to the event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entities()
    {
        return $this->belongsToMany('App\Models\Entity')->withTimestamps();
    }

    /**
     * A user can have many event responses.
     */
    public function eventResponses()
    {
        return $this->hasMany('App\Models\EventResponse');
    }

    /**
     * Get the count of users attending this event.
     */
    public function getAttendingCountAttribute()
    {
        $responses = $this->eventResponses()->get();
        $responses->filter(function ($e) {
            return 'Attending' === $e->responseType->name;
        });

        return \count($responses);
    }

    /**
     * Get the length of the event in hours.
     *
     * @ return decimal
     */
    public function getLengthInHoursAttribute()
    {
        if ($this->start_at) {
            return $this->start_at->diffInHours($this->end_time, false);
        }

        return 0;
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
        }

        return $this->start_at->addDay()->startOfDay();
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

    /**
     * Return a collection of events with the passed tag.
     *
     * @param string $tag
     */
    public static function getByTag(string $tag)
    {
        // get a list of events that have the passed tag
        return self::whereHas('tags', function (Builder $q) use ($tag) {
            $q->where('name', '=', ucfirst($tag));
        });
    }

    /**
     * Return a collection of events with the passed venue.
     *
     * @param string $slug
     */
    public static function getByVenue(string $slug)
    {
        // get a list of events that have the passed tag
        return self::whereHas('venue', function (Builder $q) use ($slug) {
            $q->where('slug', '=', $slug);
        });
    }

    /**
     * Return a collection of events with the passed event type.
     */
    public static function getByType(?string $slug): Builder
    {
        // get a list of events that have the passed tag
        return self::whereHas('eventType', function (Builder $q) use ($slug) {
            $q->where('name', '=', $slug);
        });
    }

    /**
     * Return a collection of events with the passed series.
     */
    public static function getBySeries(?string $slug): Builder
    {
        // get a list of events that have the passed tag
        return self::whereHas('series', function ($q) use ($slug) {
            $q->where('name', '=', $slug);
        });
    }

    /**
     * Return a collection of events with the passed entity.
     */
    public static function getByEntity(?string $slug): Builder
    {
        // get a list of events that have the passed entity
        return self::whereHas('entities', function ($q) use ($slug) {
            $q->where('slug', '=', $slug);
        });
    }

    /**
     * Returns the response status for the passed user.
     **/
    public function getEventResponse(User $user): ?EventResponse
    {
        return EventResponse::where('event_id', '=', $this->id)
            ->where('user_id', '=', $user->id)->first();
    }

    /**
     * Returns the review from the passed use if there is one.
     *
     * @param User $user
     *
     * @return Collection $eventReview
     *
     **/
    public function getEventReview($user)
    {
        return EventReview::where('event_id', '=', $this->id)
            ->where('user_id', '=', $user->id)->first();
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
     * Return a collection of events that begin on the passed date.
     */
    public static function getByStartAt(?DateTime $date): Collection
    {
        // get a list of events that start on the passed date
        $cdate_yesterday = Carbon::parse($date)->subDay();
        $cdate_tomorrow = Carbon::parse($date)->addDay();

        $events = self::where('start_at', '>', $cdate_yesterday->toDateString())
            ->where('start_at', '<', $cdate_tomorrow->toDateString())
            ->with('visibility')
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC');

        return $events;
    }

    public function addPhoto(Photo $photo)
    {
        return $this->photos()->attach($photo->id);
    }

    /**
     * Return the flyer for this event.
     *
     * @return Photo $photo
     *
     **/
    public function getFlyer()
    {
        // get a list of events that start on the passed date
        return $this->photos()->first();
    }

    /**
     * Return the primary photo for this event.
     *
     * @return Photo $photo
     *
     **/
    public function getPrimaryPhoto()
    {
        // gets the first photo related to this event
        return $this->photos()->where('photos.is_primary', '=', '1')->first();
    }

    /**
     * Create the slug from the name if none was passed.
     */
    public function setSlugAttribute(?string $value)
    {
        // grab the title and slugify it
        if ('' === $value) {
            $this->attributes['slug'] = Str::slug($this->name);
        } else {
            $this->attributes['slug'] = $value;
        }
    }

    public function getGoogleCalendarLink()
    {
        $action = 'TEMPLATE';
        $text = $this->name;
        $start = Carbon::parse($this->start_at)->format('Ymd\THis');
        $end = Carbon::parse($this->start_at)->format('Ymd\THis');
        $details = $this->description;
        $location = $this->venue ? $this->venue->name : 'Unknown';
        $sf = 'true';

        // TODO get this URL from config
        $url = sprintf('https://www.google.com/calendar/render?action=%s&text=%s&dates=%s/%s&details=%s&location=%s&sf=%s&output=xml', $action, $text, $start, $end, $details, $location, $sf);

        return $url;
    }

    public function getBriefFormat()
    {
        $format = $this->start_at->format('l F jS Y') . ' | ' . $this->name;

        if (!empty($this->series_id)) {
            $format .= ' ' . $this->series->name . ' series';
        }

        $format .= ' ' . $this->eventType->name;

        if ($this->venue) {
            $format .= ' at ';
            $format .= $this->venue->name ?? 'No venue specified';
        }

        if ($this->start_at) {
            $format .= ' at ' . $this->start_at->format('gA');
        }

        if ($this->door_price) {
            $format .= ' $' . number_format($this->door_price, 0);
        }

        if (!$this->entities->isEmpty()) {
            $format .= ' Related: ';
            foreach ($this->entities as $entity) {
                if ('' !== $entity->twitter_username) {
                    $format .= ' @' . $entity->twitter_username;
                } else {
                    $format .= ' @' . Str::studly($entity->slug);
                }
            }
        }

        if (!$this->tags->isEmpty()) {
            $format .= ' Tag: ';
            foreach ($this->tags as $tag) {
                $format .= ' #' . Str::studly($tag->name);
            }
        }

        if ($this->primary_link) {
            $format .= ' ' . $this->primary_link ?? '';
        }

        $format .= ' https://arcane.city/events/' . $this->id;

        return substr($format, 0, 280);
    }
}
