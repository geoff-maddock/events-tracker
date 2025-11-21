<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

/**
 * App\Models\User.
 *
 * @property mixed                                                                                                     $id
 * @property string                                                                                                    $name
 * @property string                                                                                                    $email
 * @property string                                                                                                    $password
 * @property string|null                                                                                               $remember_token
 * @property \Illuminate\Support\Carbon|null                                                                           $created_at
 * @property \Illuminate\Support\Carbon|null                                                                           $updated_at
 * @property int|null                                                                                                  $user_status_id
 * @property string|null                                                                                               $email_verified_at
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Activity[]                                           $activity
 * @property int|null                                                                                                  $activity_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[]                                            $comments
 * @property int|null                                                                                                  $comments_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\EventResponse[]                                      $eventResponses
 * @property int|null                                                                                                  $event_responses_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Event[]                                              $events
 * @property int|null                                                                                                  $events_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Follow[]                                             $follows
 * @property int|null                                                                                                  $follows_count
 * @property mixed                                                                                                     $attending_count
 * @property mixed                                                                                                     $entities_following_count
 * @property mixed                                                                                                     $event_count
 * @property mixed                                                                                                     $full_name
 * @property mixed                                                                                                     $group_list
 * @property mixed                                                                                                     $is_active
 * @property mixed                                                                                                     $series_following_count
 * @property mixed                                                                                                     $tags_following_count
 * @property mixed                                                                                                     $threads_following_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Group[]                                              $groups
 * @property int|null                                                                                                  $groups_count
 * @property \App\Models\Activity|null                                                                                 $lastActivity
 * @property \App\Models\Post|null                                                                                     $lastPost
 * @property \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property int|null                                                                                                  $notifications_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Photo[]                                              $photos
 * @property int|null                                                                                                  $photos_count
 * @property \App\Models\Profile|null                                                                                  $profile
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Series[]                                             $series
 * @property int|null                                                                                                  $series_count
 * @property UserStatus|null                                                                                           $status
 *
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserStatusId($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable implements AuthorizableContract, CanResetPasswordContract, MustVerifyEmailContract
{
    use Authorizable;
    use CanResetPassword;
    use MustVerifyEmail;
    use Notifiable;
    use HasFactory;
    use HasApiTokens;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'email', 'password', 'user_status_id'];

    public $frontendUrl = null;

    /**
     * Always hydrate these relationships.
     */
    // protected $with = ['profile'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * A how many events the user created.
     */
    public function getEventCountAttribute(): int
    {
        return $this->hasMany(Event::class, 'created_by')->count();
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    /**
     * A user can have many series.
     */
    public function series(): HasMany
    {
        return $this->hasMany(Series::class);
    }

    /**
     * A user can have much activity.
     */
    public function activity(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * A user can have many comments.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * A user can have one profile().
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * A user has a status.
     */
    public function status(): HasOne
    {
        return $this->hasOne(UserStatus::class, 'id', 'user_status_id');
    }

    /**
     * Return the primary photo for this user.
     *
     * @return Photo $photo
     *
     **/
    public function getPrimaryPhoto(): ?Photo
    {
        // get a list of events that start on the passed date
        $primary = $this->photos()->where('photos.is_primary', '=', '1')->first();

        return $primary;
    }

    /**
     * Get all of the events photos.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class)->withTimestamps();
    }

    /**
     * The links that belong to the entity.
     */
    public function links(): BelongsToMany
    {
        return $this->belongsToMany(Link::class);
    }


    /**
     * Return the count of events the user is attending.
     */
    public function getAttendingCountAttribute(): int
    {
        $responses = $this->eventResponses()->get();
        $responses->filter(function ($e) {
            return 'Attending' == $e->responseType->name;
        });

        return count($responses);
    }

    /**
     * A user can have many event responses.
     */
    public function eventResponses(): HasMany
    {
        return $this->hasMany(EventResponse::class);
    }

    /**
     * Return the count of logins the user has made.
     */
    public function getLoginCountAttribute(): int
    {
        $logins = $this->activity()->get();
        $logins->filter(function ($e) {
            return 1 == $e->action_id;
        });

        return count($logins);
    }

    /**
     * Return the count of entities the user is following.
     */
    public function getEntitiesFollowingCountAttribute(): int
    {
        $responses = $this->follows()->get();
        $responses->filter(function ($e) {
            return 'entity' == $e->object_type;
        });

        return count($responses);
    }

    /**
     * Return the count of tags the user is following.
     */
    public function getTagsFollowingCountAttribute(): int
    {
        $responses = $this->follows()->get();
        $responses->filter(function ($e) {
            return 'tag' == $e->object_type;
        });

        return count($responses);
    }

    /**
     * Return the count of series the user is following.
     */
    public function getSeriesFollowingCountAttribute(): int
    {
        $responses = $this->follows()->get();
        $responses->filter(function ($e) {
            return 'series' == $e->object_type;
        });

        return count($responses);
    }

    /**
     * Return the count of threads the user is following.
     */
    public function getThreadsFollowingCountAttribute(): int
    {
        $responses = $this->follows()->get();
        $responses->filter(function ($e) {
            return 'thread' == $e->object_type;
        });

        return count($responses);
    }

    /**
     * A user can follow many objects.
     */
    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class);
    }

    /**
     * A user can own many objects.
     */
    public function owns(Event | Entity | Forum $object): bool
    {
        return $object->created_by == $this->id;
    }

    /**
     * An profile is owned by a user.
     */
    public function getFullNameAttribute(): ?string
    {
        if ($profile = $this->profile) {
            $full = $profile->first_name.' '.$profile->last_name;

            return strlen($full) > 1 ? $full : $this->name; //$profile->first_name.' '.$profile->last_name;
        }

        return $this->name;
    }

    /**
     * Return a list of events the user is attending in the future.
     */
    public function getAttendingFuture(): Collection
    {
        $events = Event::join('event_responses', 'events.id', '=', 'event_responses.event_id')
            ->join('response_types', 'event_responses.response_type_id', '=', 'response_types.id')
            ->where('response_types.name', '=', 'Attending')
            ->where('event_responses.user_id', '=', $this->id)
            ->where('start_at', '>=', Carbon::today()->startOfDay())
            ->orderBy('events.start_at', 'asc')
            ->select('events.*')
            ->get();

        return $events;
    }

    /**
     * Return a list of events the user is attending in the future.
     */
    public function getAttendingToday(): Collection
    {
        $events = Event::join('event_responses', 'events.id', '=', 'event_responses.event_id')
            ->join('response_types', 'event_responses.response_type_id', '=', 'response_types.id')
            ->where('response_types.name', '=', 'Attending')
            ->where('event_responses.user_id', '=', $this->id)
            ->where('start_at', '>=', Carbon::today()->startOfDay())
            ->where('start_at', '<', Carbon::tomorrow()->startOfDay())
            ->orderBy('events.start_at', 'asc')
            ->select('events.*')
            ->get();

        return $events;
    }

    /**
     * Return a list of events the user is attending.
     * @return Builder<Event>
     */
    public function getAttending(): Builder
    {
        $events = Event::join('event_responses', 'events.id', '=', 'event_responses.event_id')
            ->join('response_types', 'event_responses.response_type_id', '=', 'response_types.id')
            ->where('response_types.name', '=', 'Attending')
            ->where('event_responses.user_id', '=', $this->id)
            ->select('events.*');

        return $events;
    }

    /**
     * Return a query builder for entities the user is following.
     */
    public function getFollowingEntities(): Builder
    {
        $entities = Entity::join('follows', 'entities.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'entity')
            ->where('follows.user_id', '=', $this->id)
            ->select('entities.*');

        return $entities;
    }

    /**
     * Return a list of entities the user is following.
     */
    public function getEntitiesFollowing(): Collection
    {
        return $this->getFollowingEntities()
            ->orderBy('entities.name', 'asc')
            ->get();
    }

    /**
     * Return a count of entities the user is following.
     */
    public function countEntitiesFollowing(): int
    {
        $entities = Entity::join('follows', 'entities.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'entity')
            ->where('follows.user_id', '=', $this->id)
            ->select('entities.*')
            ->count();

        return $entities;
    }

    /**
     * Return a list of tags the user is following.
     */
    public function getTagsFollowing(): Collection
    {
        $tags = Tag::join('follows', 'tags.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'tag')
            ->where('follows.user_id', '=', $this->id)
            ->orderBy('tags.name', 'asc')
            ->select('tags.*')
            ->get();

        return $tags;
    }

    /**
     * Return a count of tags the user is following.
     */
    public function countTagsFollowing(): int
    {
        $tags = Tag::join('follows', 'tags.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'tag')
            ->where('follows.user_id', '=', $this->id)
            ->orderBy('tags.name', 'asc')
            ->select('tags.*')
            ->count();

        return $tags;
    }

    /**
     * Return a list of series the user is following.
     */
    public function getSeriesFollowing(): Collection
    {
        $series = Series::join('follows', 'series.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'series')
            ->where('follows.user_id', '=', $this->id)
            ->orderBy('series.name', 'asc')
            ->select('series.*')
            ->get();

        return $series;
    }

    /**
     * Return a count of series the user is following.
     */
    public function countSeriesFollowing(): int
    {
        $series = Series::join('follows', 'series.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'series')
            ->where('follows.user_id', '=', $this->id)
            ->orderBy('follows.created_at', 'desc')
            ->select('series.*')
            ->count();

        return $series;
    }

    /**
     * Return a list of threads the user is following.
     */
    public function getThreadsFollowing(): Collection
    {
        $threads = Thread::join('follows', 'threads.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'thread')
            ->where('follows.user_id', '=', $this->id)
            ->orderBy('follows.created_at', 'desc')
            ->select('threads.*')
            ->get();

        return $threads;
    }

    /**
     * Return a count of threads the user is following.
     */
    public function countThreadsFollowing(): int
    {
        $threads = Thread::join('follows', 'threads.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'thread')
            ->where('follows.user_id', '=', $this->id)
            ->orderBy('follows.created_at', 'desc')
            ->select('threads.*')
            ->count();

        return $threads;
    }

    /**
     * Return the tags the user is following.
     */
    public function followedTags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'follows', 'user_id', 'object_id')
            ->wherePivot('object_type', 'tag')
            ->orderBy('tags.name', 'asc');
    }

    /**
     * Events that were created by the user.
     */
    public function createdEvents(): Collection
    {
        $events = $this->events()->where('created_at', '=', Auth::user())->orderBy('start_at', 'ASC')->get();

        return $events;
    }

    /**
     * A user can have many events.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by')->orderBy('start_at', 'DESC');
    }

    public function addPhoto(Photo $photo): void
    {
        $this->photos()->attach($photo->id);
    }

    public function hasGroup(string $group): bool
    {
        return $this->groups->contains('name', $group);
    }

    public function hasPermission(string $permission): bool
    {
        return $this->getPermissions()->contains('name', $permission);
    }

    public function assignGroup(string $group): Model
    {
        return $this->groups()->save(
            Group::whereName($group)->firstOrFail()
        );
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * Get all permissions for the user through their groups.
     */
    public function getPermissions(): SupportCollection
    {
        return $this->groups->map(function ($group) {
            return $group->permissions;
        })->flatten()->unique('id');
    }

    /**
     * Fetch the last published post for the user.
     */
    public function lastPost(): HasOne
    {
        return $this->hasOne(Post::class, 'created_by')->latest();
    }

    /**
     * Fetch the login date for the user.
     */
    public function lastActivity(): HasOne
    {
        return $this->hasOne(Activity::class, 'user_id')->latestOfMany();
    }

    /**
     * Check that the user is active.
     */
    public function getIsActiveAttribute(): bool
    {
        if ($this->status && 'Active' === $this->status->name) {
            return true;
        }

        return false;
    }

    /**
     * Return the feed of user activity.
     */
    public function feed(User $user, int $take = 50): SupportCollection
    {
        return static::where('user_id', $user->id)
            ->latest()
            // ->with('object')
            ->take($take)
            ->get()
            ->groupBy(function ($activity) {
                return $activity->created_at->format('Y-m-d');
            });
    }

    /**
     * Get a list of group ids associated with the user.
     */
    public function getGroupListAttribute(): array
    {
        return $this->groups->pluck('id')->all();
    }

        /**
     * Return the entities the user is following.
     */
    public function followedEntities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class, 'follows', 'user_id', 'object_id')
            ->wherePivot('object_type', 'entity')
            ->orderBy('entities.name', 'asc');
    }

    /**
     * Return the series the user is following.
     */
    public function followedSeries(): BelongsToMany
    {
        return $this->belongsToMany(Series::class, 'follows', 'user_id', 'object_id')
            ->wherePivot('object_type', 'series')
            ->orderBy('series.name', 'asc');
    }

    /**
     * Return the threads the user is following.
     */
    public function followedThreads(): BelongsToMany
    {
        return $this->belongsToMany(Thread::class, 'follows', 'user_id', 'object_id')
            ->wherePivot('object_type', 'thread')
            ->orderBy('follows.created_at', 'desc');
    }

    /**
     * Get the events related to entities or tags that the user is following.
     *
     * @return Collection 
     */
    public function followedEvents(): Collection
    {
        $tags = $this->getTagsFollowing();
        $entities = $this->getEntitiesFollowing();
        $attendingEvents = $this->getAttendingFuture();

        // get events related to the followed tags where the event 
        $tagEvents = Event::whereHas('tags', function ($query) use ($tags) {
            $query->whereIn('tags.id', $tags->pluck('id'));
            $query->where('start_at', '>=', Carbon::now());
        })->get();


        // get events related to the followed entities
        $entityEvents = Event::whereHas('entities', function ($query) use ($entities) {
            $query->whereIn('entities.id', $entities->pluck('id'));
            $query->where('start_at', '>=', Carbon::now());
        })->get();

        // merge the events and return unique events
        $events = $tagEvents->merge($entityEvents)->merge($attendingEvents)->unique();

        return $events;
    }

    /**
     * Return a query of future events related to followed entities, series or tags.
     *
     * @return Builder<Event>
     */
    public function getRecommendedEvents(): Builder
    {
        $tagIds = $this->getTagsFollowing()->pluck('id');
        $entityIds = $this->getEntitiesFollowing()->pluck('id');
        $seriesIds = $this->getSeriesFollowing()->pluck('id');

        if ($tagIds->isEmpty() && $entityIds->isEmpty() && $seriesIds->isEmpty()) {
            return Event::query()->whereRaw('1 = 0');
        }

        $events = Event::query()->where('start_at', '>=', Carbon::now());

        $events->where(function ($query) use ($tagIds, $entityIds, $seriesIds) {
            $added = false;
            if ($tagIds->isNotEmpty()) {
                $query->whereHas('tags', function ($q) use ($tagIds) {
                    $q->whereIn('tags.id', $tagIds);
                });
                $added = true;
            }

            if ($entityIds->isNotEmpty()) {
                $method = $added ? 'orWhereHas' : 'whereHas';
                $query->{$method}('entities', function ($q) use ($entityIds) {
                    $q->whereIn('entities.id', $entityIds);
                });
                $added = true;
            }

            if ($seriesIds->isNotEmpty()) {
                if ($added) {
                    $query->orWhereIn('series_id', $seriesIds);
                } else {
                    $query->whereIn('series_id', $seriesIds);
                }
            }
        });

        return $events->orderBy('events.start_at', 'asc');
    }
}
