<?php

namespace App;

use App\Filters\QueryFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Entity extends Eloquent
{
    protected $fillable = [
        'name', 'slug', 'short', 'description', 'entity_type_id', 'entity_status_id', 'entity_address_id', 'facebook_username', 'twitter_username', 'created_by',
    ];
    protected $dates = ['updated_at'];

    protected $attributes = [
        'description' => '',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($entity) {
            if (Auth::user()) {
                $entity->created_by = Auth::user()->id;
                $entity->updated_by = Auth::user()->id;
            }
            $entity->short = '';
        });

        static::updating(function ($entity) {
            $entity->updated_by = Auth::user()->id;
        });
    }

    /**
     * Return a collection of entities with the role venue.
     *
     **/
    public static function getVenues(): Builder
    {
        // get a list of venues
        $venues = self::whereHas('roles', function ($q) {
            $q->where('name', '=', 'Venue');
        })->orderBy('name', 'ASC');

        return $venues;
    }

    /**
     * Return a collection of entities with the role  promoter.
     *
     **/
    public static function getPromoters(): Builder
    {
        // get a list of venues
        $venues = self::whereHas('roles', function ($q) {
            $q->where('name', '=', 'Promoter');
        })->orderBy('name', 'ASC');

        return $venues;
    }

    /**
     * Return a collection of entities with the passed role.
     **/
    public static function getByRole($role): Builder
    {
        // get a list of entities that have the passed role
        $entities = self::whereHas('roles', function ($q) use ($role) {
            $q->where('slug', '=', strtolower($role));
        })->orderBy('name', 'ASC');

        return $entities;
    }

    /**
     * Return a collection of entities with the passed tag.
     *
     **/
    public static function getByTag($tag): Builder
    {
        // get a list of entities that have the passed tag
        $entities = self::whereHas('tags', function ($q) use ($tag) {
            $q->where('name', '=', ucfirst($tag));
        })->orderBy('name', 'ASC');

        return $entities;
    }

    /**
     * Return a collection of entities with the passed alias.
     *
     * @pra
     *
     * @return Collection $entities
     *
     **/
    public static function getByAlias($alias): Collection
    {
        // get a list of entities that have the passed alias
        $entities = self::whereHas('aliases', static function ($q) use ($alias) {
            $q->where('name', '=', ucfirst($alias));
        })->orderBy('name', 'ASC');

        return $entities;
    }

    /**
     * Return an entity.
     *
     * @pra
     *
     * @return Entity $entity
     *
     **/
    public static function getBySlug($slug)
    {
        // get a single entity by slug
        $entity = self::where('slug', $slug);

        return $entity;
    }

    /**
     * Return a collection of entities with the passed type.
     *
     * @pra
     *
     * @return Collection $entities
     *
     **/
    public static function getByType($type)
    {
        // get a list of entities that have the passed role
        $entities = self::whereHas('entity_type', function ($q) use ($type) {
            $q->where('name', '=', ucfirst($type));
        })->orderBy('name', 'ASC');

        return $entities;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get all of the entities comments.
     */
    public function comments()
    {
        return $this->morphMany('App\Comment', 'commentable')->orderBy('created_at', 'DESC');
    }

    /**
     * Applies the builder filters.
     */
    public function scopeFilter(Builder $builder, QueryFilter $filters): Builder
    {
        return $filters->apply($builder);
    }

    /**
     * Returns entities by type.
     */
    public function scopeOfType($query, $type)
    {
        $type = EntityType::where('name', '=', $type)->first();

        return $query->where('entity_type_id', '=', $type ? $type->id : null);
    }

    /**
     * Returns active entities.
     */
    public function scopeActive($query)
    {
        $status = EntityStatus::where('name', '=', 'Active')->first();

        return $query->where('entity_status_id', '=', $status ? $status->id : null);
    }

    /**
     * Returns entities created by the user.
     *
     * @ param User $user
     */
    public function scopeOwnedBy($query, User $user)
    {
        return $query->where('created_by', '=', $user ? $user->id : null);
    }

    /**
     * Returns entities that are promoters.
     */
    public function scopePromoter($query, string $type)
    {
        $type = EntityType::where('name', '=', $type)->first();

        return $query->where('entity_type_id', '=', $type->id);
    }

    /**
     * An entity is owned by a user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\User', 'created_by');
    }

    /**
     * The contacts that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function contacts()
    {
        return $this->belongsToMany('App\Contact');
    }

    /**
     * The contacts that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function series()
    {
        return $this->belongsToMany('App\Series');
    }

    /**
     * The links that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function links()
    {
        return $this->belongsToMany('App\Link');
    }

    /**
     * An entity has one entity type.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entityType()
    {
        return $this->belongsTo('App\EntityType');
    }

    /**
     * An entity has one status.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entityStatus(): BelongsTo
    {
        return $this->belongsTo('App\EntityStatus');
    }

    /**
     * The tags that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany('App\Tag')->withTimestamps();
    }

    /**
     * The aliases that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function aliases(): BelongsToMany 
    {
        return $this->belongsToMany('App\Alias')->withTimestamps();
    }

    /**
     * The follows that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\Morph
     */
    public function follows()
    {
        return $this->morphMany('App\Follow', 'object', 'object_type', 'object_id');
    }

    /**
     * The likes that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\Morph
     */
    public function likes()
    {
        return $this->morphMany('App\Like', 'object', 'object_type', 'object_id');
    }

    /**
     * If there is a future event, return it.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function futureEvents()
    {
        $events = $this->events()->where('start_at', '>=', Carbon::now())->orderBy('start_at', 'ASC')->get();

        return $events;
    }

    /**
     * The events that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function events()
    {
        return $this->belongsToMany('App\Event')->with('visibility', 'venue')->withTimestamps();
    }

    /**
     * Return any events that match today for the start date.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function todaysEvents()
    {
        $events = $this->events()->whereDate('start_at', '=', Carbon::today()->toDateString())->orderBy('start_at', 'ASC')->get();

        return $events;
    }

    /**
     * If there is a future event, return it.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function pastEvents($rpp = null)
    {
        $events = $this->events()
            ->where('start_at', '<', Carbon::now())
            ->orderBy('start_at', 'DESC')
            ->paginate($rpp);

        return $events;
    }

    /**
     * Events that occurred at this venue.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function eventsAtVenue()
    {
        $events = Event::where('venue_id', $this->id)->orderBy('start_at', 'ASC')->get();

        return $events;
    }

    /**
     * Get a list of tag ids associated with the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getTagListAttribute()
    {
        return $this->tags->pluck('id')->all();
    }

    /**
     * Get a list of alias ids associated with the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getAliasListAttribute()
    {
        return $this->aliases->pluck('id')->all();
    }

    /**
     * Get a list of role ids associated with the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getRoleListAttribute()
    {
        return $this->roles->pluck('id')->all();
    }

    public function addPhoto(Photo $photo)
    {
        return $this->photos()->attach($photo->id);
    }

    /**
     * Get all of the entities photos.
     */
    public function photos()
    {
        return $this->belongsToMany('App\Photo')->withTimestamps();
    }

    /**
     * Return the primary photo for this entity.
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
     * Return the primary location for this entity.
     *
     * @return Location
     *
     **/
    public function getPrimaryLocation()
    {
        // get a list of events that start on the passed date
        $primary = $this->locations()->first();

        return $primary;
    }

    /**
     * The locations that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function locations()
    {
        return $this->hasMany('App\Location')->with('visibility');
    }

    /**
     * Return the primary location address.
     *
     * @return Location
     *
     **/
    public function getPrimaryLocationAddress($signedIn = null)
    {
        $address = '';

        // get a list of events that start on the passed date
        $primary = $this->locations()->first();

        if ($primary && ('Guarded' != $primary->visibility->name || ('Guarded' == $primary->visibility->name) && $signedIn)) {
            $address .= $primary->address_one.' ';
            $address .= $primary->city;
        }

        return $address;
    }

    /**
     * Checks if the entity is followed by the user.
     *
     * @return Collection $follows
     *
     **/
    public function followedBy($user)
    {
        $response = Follow::where('object_type', '=', 'entity')
            ->where('object_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->first();
        // return any follow instances

        return $response;
    }

    /**
     * Returns the users that follow the entity.
     *
     * @return Collection $follows
     *
     **/
    public function followers()
    {
        $users = User::join('follows', 'users.id', '=', 'follows.user_id')
            ->where('follows.object_type', 'entity')
            ->where('follows.object_id', $this->id)
            ->get();

        return $users;
    }

    /**
     * Checks if the entity is liked by the user.
     *
     * @return Collection $likes
     *
     **/
    public function likedBy($user)
    {
        $response = Like::where('object_type', '=', 'entity')
            ->where('object_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->first();
        // return any like instances

        return $response;
    }

    /**
     * Returns the users that like the entity.
     *
     * @return Collection $likes
     *
     **/
    public function likers()
    {
        $users = User::join('likes', 'users.id', '=', 'follows.user_id')
            ->where('likes.object_type', 'entity')
            ->where('likes.object_id', $this->id)
            ->get();

        return $users;
    }

    public function hasRole($role)
    {
        $role = $this->roles()->where('name', $role)->count();

        return $role;
    }

    /**
     * The roles that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany('App\Role')->withTimestamps();
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
}
