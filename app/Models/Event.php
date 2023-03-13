<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Carbon\Carbon;
use DateTime;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * App\Models\Event.
 *
 * @property int                                                                                                       $id
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
 * @property \Illuminate\Support\Carbon                                                                                $created_at
 * @property \Illuminate\Support\Carbon                                                                                $updated_at
 * @property \Illuminate\Support\Carbon|null                                                                           $cancelled_at
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[]                                            $comments
 * @property int|null                                                                                                  $comments_count
 * @property \App\Models\User                                                                                          $creator
 * @property int|null                                                                                                  $entities_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\EventResponse[]                                      $eventResponses
 * @property int|null                                                                                                  $event_responses_count
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
 * @property \App\Models\Entity|null                                                                                   $venue
 * @property \App\Models\Visibility|null                                                                               $visibility
 * @mixin \Illuminate\Database\Eloquent\Relations\Relation
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
 * @method static Builder|Event getByEntity($value)
 */
class Event extends Model
{
    use Notifiable;
    use HasFactory;

    public static function boot()
    {
        parent::boot();

        // TODO Fix the default after I resolve user setup in API
        static::creating(function ($event) {
            $user = Auth::user();
            $event->created_by = $user ? $user->id : 1;
            $event->updated_by = $user ? $user->id : 1;
        });

        static::updating(function ($event) {
            $user = Auth::user();
            $event->updated_by = $user ? $user->id : 1;
        });
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
        'min_age',
        'created_by',
    ];

    protected $casts = [
        'soundcheck_at' => 'datetime',
        'door_at' => 'datetime',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];



    /**
     * @return Builder<Event>
     */
    public function scopeFilter(Builder $query, QueryFilter $filters): Builder
    {
        return $filters->apply($query);
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
     * Set the cancelled at attribute.
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
     * Set the door_price attribute.
     */
    public function setDoorPriceAttribute(?string $price): void
    {
        if (!empty($price)) {
            $this->attributes['door_price'] = $price;
        } else {
            $this->attributes['door_price'] = null;
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
     * Set the series attribute.
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
    public function setPresalePriceAttribute(?float $value): void
    {
        if (!empty($value)) {
            $this->attributes['presale_price'] = $value;
        } else {
            $this->attributes['presale_price'] = null;
        }
    }

    /**
     * @param Builder<Event> $query
     */
    public function scopeToday(Builder $query): Builder
    {
        /* @var Builder<Event>*/
        return $query->whereDate('start_at', '=', Carbon::today()->toDateString())
            ->orderBy('start_at', 'asc');
    }

    /**
     * @param Builder<Event> $query 
     */
    public function scopeFuture(Builder $query): Builder
    {
        return $query->where('start_at', '>=', Carbon::today()->startOfDay())
            ->orderBy('start_at', 'asc');
    }

    /**
     * @param Builder<Event> $query
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_at', '<', Carbon::today()->startOfDay())
            ->orderBy('start_at', 'desc');
    }

    /**
     * Returns visible events.
     * @return Builder<Event>
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
     * Returns visible events.
     */
    public function scopeStarting(Builder $query, ?string $date): Builder
    {
        $cdate_yesterday = Carbon::parse($date)->subDay();
        $cdate_tomorrow = Carbon::parse($date)->addDay();

        return $query->where('start_at', '>', $cdate_yesterday->toDateString().' 23:59:59')
            ->where('start_at', '<', $cdate_tomorrow->toDateString().' 00:00:00')
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
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Photo')->withTimestamps();
    }

    /**
     * An event can belong to many threads.
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
     */
    public function series(): HasOne
    {
        return $this->hasOne(Series::class, 'id', 'series_id');
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
     * Get the count of users attending this event.
     */
    public function getAttendingCountAttribute(): int
    {
        $responses = $this->eventResponses()->get();
        $responses->filter(function ($e) {
            return 'Attending' === $e->responseType->name;
        });

        return \count($responses);
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
            return $this->start_at->diffInHours($this->end_time, false);
        }

        return 0;
    }

    /**
     * Get the end time of the event.
     */
    public function getEndTimeAttribute(): Carbon
    {
        if (isset($this->end_at)) {
            return $this->end_at;
        }

        return $this->start_at->addDay()->startOfDay();
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
     * Return a collection of events with the passed event type.
     * @return Builder<Event>
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
     */
    public function performerEntities(?int $rpp = null): LengthAwarePaginator
    {
        return $this->entities()->whereHas('roles', function ($q) {
            $q->whereIn('slug', ['dj', 'band', 'producer']);
        })->orderBy('name', 'ASC')->paginate($rpp);
    }

    /**
     * Returns the response status for the passed user.
     **/
    public function getEventResponse(User $user): ?EventResponse
    {
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
     * Returns the review from the passed use if there is one.
     *
     **/
    public function getEventReview(User $user): ?EventReview
    {
        return EventReview::where('event_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->first();
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
     * @return Builder<Event>
     */
    public static function getByStartAt(?DateTime $date): Builder
    {
        // get a list of events that start on the passed date
        $cdate_yesterday = Carbon::parse($date)->subDay();
        $cdate_tomorrow = Carbon::parse($date)->addDay();

        return self::where('start_at', '>', $cdate_yesterday->toDateString())
            ->where('start_at', '<', $cdate_tomorrow->toDateString())
            ->with('visibility')
            ->orderBy('start_at', 'ASC')
            ->orderBy('name', 'ASC');
    }

    public function addPhoto(Photo $photo): void
    {
        $this->photos()->attach($photo->id);
    }

    /**
     * Return the flyer for this event.
     *
     **/
    public function getFlyer(): ?Photo
    {
        // get a list of events that start on the passed date
        return $this->photos()->first();
    }

    /**
     * Return the primary photo for this event.
     *
     **/
    public function getPrimaryPhoto(): ?Photo
    {
        // gets the first photo related to this event
        return $this->photos()->where('photos.is_primary', '=', '1')->first();
    }

    /**
     * Create the slug from the name if none was passed.
     */
    public function setSlugAttribute(?string $value): void
    {
        // grab the title and slugify it
        if ('' === $value) {
            $this->attributes['slug'] = Str::slug($this->name);
        } else {
            $this->attributes['slug'] = $value;
        }
    }

    public function getGoogleCalendarLink(): ?string
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

    public function getDateLastTitleFormat(): ?string
    {
        $format = $this->name;

        // include the location of the event
        if ($this->venue) {
            $format .= ' at ';
            $format .= $this->venue->name ?? 'No venue specified';
        }

        $format .= ' on '.$this->start_at->format('D F jS');

        return $format;
    }

    public function getDateFirstTitleFormat(): ?string
    {
        $format = $this->start_at->format('D F jS').' '.$this->name;

        // include the location of the event
        if ($this->venue) {
            $format .= ' at ';
            $format .= $this->venue->name ?? 'No venue specified';
        }

        return $format;
    }

    public function getTitleFormat(): ?string
    {
        $format = $this->start_at->format('l F jS Y').' '.$this->name;

        // include the location of the event
        if ($this->venue) {
            $format .= ' at ';
            $format .= $this->venue->name ?? 'No venue specified';
        }

        return $format;
    }

    // Format the event to post as a tweet
    public function getBriefFormat(): ?string
    {
        // max length 280 chars
        // URLs count as 23 chars

        // add the date and name - always include this
        $format = $this->start_at->format('l F jS Y').' | '.$this->name;

        // if part of a series, include the series
        if (!empty($this->series_id)) {
            $format .= ' '.$this->series->name.' series';
        }

        // include the type of event
        $format .= ' '.$this->eventType->name;

        // include the location of the event
        if ($this->venue) {
            $format .= ' at ';
            $format .= $this->venue->name ?? 'No venue specified';
        }

        // include the start time
        if ($this->start_at) {
            $format .= ' at '.$this->start_at->format('gA');
        }

        // include the door price
        if ($this->door_price) {
            $format .= ' $'.number_format(floatval($this->door_price), 0);
        }

        // include the related entities
        if (!$this->entities->isEmpty()) {
            foreach ($this->entities as $entity) {
                if (!empty($entity->twitter_username)) {
                    // check the length of the tag and if there is enough room to add
                    if (strlen($format) + strlen($entity->twitter_username) > 244) {
                        continue;
                    }

                    // this was using an @ mention, but changing to hashtag, see https://github.com/geoff-maddock/events-tracker/issues/555
                    $format .= ' #'.$entity->twitter_username;
                } else {
                    // check the length of the tag and if there is enough room to add
                    if (strlen($format) + strlen($entity->slug) > 244) {
                        continue;
                    }

                    // if the twitter username isn't set, then just add a hashtag
                    $format .= ' #'.Str::studly($entity->slug);
                }
            }
        }

        // Turn related tags into hashtags
        if (!$this->tags->isEmpty()) {
            foreach ($this->tags as $tag) {
                // check the length of the tag and if there is enough room to add
                if (strlen($format) + strlen($tag->name) > 244) {
                    continue;
                }
                $format .= ' #'.Str::studly($tag->name);
            }
        }

        // if there are more than 12 chars remaining, add default hashtag
        if (strlen($format) < 246) {
            $format .= ' #'.config('app.default_hashtag');
        }

        // add the arcane city URL
        if (strlen($format) < 258) {
            $format .= ' https://arcane.city/events/'.$this->id;
        }

        // add the primary link
        if ($this->primary_link) {
            // if there are at least 23 chars remaining, add primary link
            if (strlen($format) < 258) {
                $format .= ' '.$this->primary_link;
            }
        }

        // only return the first 280 chars
        return substr($format, 0, 280);
    }
}
