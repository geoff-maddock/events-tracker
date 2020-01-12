<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;

/**
 * @property mixed id
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, MustVerifyEmail
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use Notifiable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password', 'user_status_id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * A how many events the user created.
     */
    public function getEventCountAttribute()
    {
        return $this->hasMany('App\Event', 'created_by')->count();
    }

    /**
     * A user can have many series.
     */
    public function series()
    {
        return $this->hasMany('App\Series');
    }

    /**
     * A user can have much activity.
     */
    public function activity()
    {
        return $this->hasMany('App\Activity');
    }

    /**
     * A user can have many comments.
     */
    public function comments()
    {
        return $this->hasMany('App\Comment');
    }

    /**
     * A user can have one profile().
     */
    public function profile()
    {
        return $this->hasOne('App\Profile');
    }

    /**
     * A user has a status.
     */
    public function status()
    {
        return $this->hasOne('App\UserStatus', 'id', 'user_status_id');
    }

    /**
     * Return the primary photo for this user.
     *
     * @return Photo $photo
     *
     **/
    public function getPrimaryPhoto()
    {
        // get a list of events that start on the passed date
        $primary = $this->photos()->where('photos.is_primary', '=', '1')->first();

        return $primary;
    }

    /**
     * Get all of the events photos.
     */
    public function photos()
    {
        return $this->belongsToMany('App\Photo')->withTimestamps();
    }

    /**
     * Return the count of events the user is attending.
     */
    public function getAttendingCountAttribute()
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
    public function eventResponses()
    {
        return $this->hasMany('App\EventResponse');
    }

    /**
     * Return the count of entities the user is following.
     */
    public function getEntitiesFollowingCountAttribute()
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
    public function getTagsFollowingCountAttribute()
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
    public function getSeriesFollowingCountAttribute()
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
    public function getThreadsFollowingCountAttribute()
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
    public function follows()
    {
        return $this->hasMany('App\Follow');
    }

    /**
     * An profile is owned by a user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function getFullNameAttribute()
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
    public function getAttendingFuture()
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
    public function getAttendingToday()
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
     */
    public function getAttending()
    {
        $events = Event::join('event_responses', 'events.id', '=', 'event_responses.event_id')
            ->join('response_types', 'event_responses.response_type_id', '=', 'response_types.id')
            ->where('response_types.name', '=', 'Attending')
            ->where('event_responses.user_id', '=', $this->id)
            ->orderBy('events.start_at', 'desc')
            ->select('events.*');

        return $events;
    }

    /**
     * Return a list of entities the user is following.
     */
    public function getEntitiesFollowing()
    {
        $entities = Entity::join('follows', 'entities.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'entity')
            ->where('follows.user_id', '=', $this->id)
            ->orderBy('follows.created_at', 'desc')
            ->select('entities.*')
            ->get();

        return $entities;
    }

    /**
     * Return a list of tags the user is following.
     */
    public function getTagsFollowing()
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
     * Return a list of series the user is following.
     */
    public function getSeriesFollowing()
    {
        $series = Series::join('follows', 'series.id', '=', 'follows.object_id')
            ->where('follows.object_type', '=', 'series')
            ->where('follows.user_id', '=', $this->id)
            ->orderBy('follows.created_at', 'desc')
            ->select('series.*')
            ->get();

        return $series;
    }

    /**
     * Return a list of threads the user is following.
     */
    public function getThreadsFollowing()
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
     * Events that were created by the user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function createdEvents()
    {
        $events = $this->events()->where('created_at', '=', Auth::user())->orderBy('start_at', 'ASC')->get();

        return $events;
    }

    /**
     * A user can have many events.
     */
    public function events()
    {
        return $this->hasMany('App\Event', 'created_by')->orderBy('start_at', 'DESC');
    }

    public function addPhoto(Photo $photo)
    {
        return $this->photos()->attach($photo->id);
    }

    public function hasGroup($group)
    {
        if (is_string($group)) {
            return $this->groups->contains('name', $group);
        }

        return (bool) $group->intersect($this->groups)->count();
    }

    public function assignGroup($group)
    {
        return $this->groups()->save(
            Group::whereName($group)->firstOrFail()
        );
    }

    /**
     * @return BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * Fetch the last published post for the user.
     *
     * @return HasOne
     */
    public function lastPost()
    {
        return $this->hasOne(Post::class, 'created_by')->latest();
    }

    /**
     * Fetch the login date for the user.
     *
     * @return HasOne
     */
    public function lastActivity()
    {
        return $this->hasOne(Activity::class, 'user_id')->latest();
    }

    /**
     * Check that the user is active.
     *
     * @ return boolean
     */
    public function getIsActiveAttribute()
    {
        if ($this->status && 'Active' === $this->status->name) {
            return 1;
        }

        return 0;
    }

    /**
     * Return the feed of user activity.
     *
     * @param $user
     * @param int $take
     *
     * @return array
     */
    public function feed($user, $take = 50)
    {
        return static::where('user_id', $user->id)
            ->latest()
            ->with('object')
            ->take($take)
            ->get()
            ->groupBy(function ($activity) {
                return $activity->created_at->format('Y-m-d');
            });
    }

    /**
     * Get a list of group ids associated with the user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getGroupListAttribute()
    {
        return $this->groups->pluck('id')->all();
    }

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        // TODO: Implement hasVerifiedEmail() method.
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        // TODO: Implement markEmailAsVerified() method.
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        // TODO: Implement sendEmailVerificationNotification() method.
    }
}
