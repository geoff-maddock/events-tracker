<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * App\Models\Entity
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $short
 * @property string $description
 * @property int|null $entity_type_id
 * @property int|null $entity_status_id
 * @property int $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property string|null $facebook_username
 * @property string|null $twitter_username
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Alias[] $aliases
 * @property-read int|null $aliases_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $comments
 * @property-read int|null $comments_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Contact[] $contacts
 * @property-read int|null $contacts_count
 * @property-read \App\Models\EntityStatus|null $entityStatus
 * @property-read \App\Models\EntityType|null $entityType
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Event[] $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Follow[] $follows
 * @property-read int|null $follows_count
 * @property-read mixed $alias_list
 * @property-read mixed $role_list
 * @property-read mixed $tag_list
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Like[] $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Link[] $links
 * @property-read int|null $links_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Location[] $locations
 * @property-read int|null $locations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Photo[] $photos
 * @property-read int|null $photos_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Series[] $series
 * @property-read int|null $series_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Thread[] $threads
 * @property-read int|null $threads_count
 * @property-read \App\Models\User $user
 * @method static Builder|Entity active()
 * @method static Builder|Entity filter(\App\Filters\QueryFilter $filters)
 * @method static Builder|Entity newModelQuery()
 * @method static Builder|Entity newQuery()
 * @method static Builder|Entity ofType($type)
 * @method static Builder|Entity ownedBy(\App\Models\User $user)
 * @method static Builder|Entity promoter(string $type)
 * @method static Builder|Entity query()
 * @method static Builder|Entity whereCreatedAt($value)
 * @method static Builder|Entity whereCreatedBy($value)
 * @method static Builder|Entity whereDescription($value)
 * @method static Builder|Entity whereEntityStatusId($value)
 * @method static Builder|Entity whereEntityTypeId($value)
 * @method static Builder|Entity whereFacebookUsername($value)
 * @method static Builder|Entity whereId($value)
 * @method static Builder|Entity whereName($value)
 * @method static Builder|Entity whereShort($value)
 * @method static Builder|Entity whereSlug($value)
 * @method static Builder|Entity whereTwitterUsername($value)
 * @method static Builder|Entity whereUpdatedAt($value)
 * @method static Builder|Entity whereUpdatedBy($value)
 * @mixin \Eloquent
 */
class Entity extends Eloquent
{
    use HasFactory;

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
            $q->where('slug', '=', $tag);
        })->orderBy('name', 'ASC');

        return $entities;
    }

    /**
     * Return a collection of entities with the passed alias.
     *
     **/
    public static function getByAlias(string $alias): Builder
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
    public static function getBySlug(string $slug)
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
    public static function getByType(string $type)
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
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at', 'DESC');
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
    public function scopeOfType(Builder $query, string $type): Builder
    {
        $type = EntityType::where('name', '=', $type)->first();

        return $query->where('entity_type_id', '=', $type ? $type->id : null);
    }

    /**
     * Returns active entities.
     */
    public function scopeActive($query): Builder
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
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The contacts that belong to the entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class);
    }

    /**
     * The contacts that belong to the entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function series()
    {
        return $this->belongsToMany(Series::class);
    }

    /**
     * The links that belong to the entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function links()
    {
        return $this->belongsToMany(Link::class);
    }

    /**
     * An entity has one entity type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entityType()
    {
        return $this->belongsTo(EntityType::class);
    }

    /**
     * An entity has one status.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entityStatus(): BelongsTo
    {
        return $this->belongsTo(EntityStatus::class);
    }

    /**
     * The tags that belong to the entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * The aliases that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function aliases(): BelongsToMany
    {
        return $this->belongsToMany(Alias::class)->withTimestamps();
    }

    /**
     * The follows that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\Morph
     */
    public function follows()
    {
        return $this->morphMany(Follow::class, 'object', 'object_type', 'object_id');
    }

    /**
     * The likes that belong to the entity.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\Morph
     */
    public function likes()
    {
        return $this->morphMany(Like::class, 'object', 'object_type', 'object_id');
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
        return $this->belongsToMany(Event::class)->with('visibility', 'venue')->withTimestamps();
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
        return $this->belongsToMany(Photo::class)->withTimestamps();
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
        return $this->hasMany(Location::class)->with('visibility');
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
            $address .= $primary->address_one . ' ';
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
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Get the threads that belong to the series.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function threads()
    {
        return $this->belongsToMany(Thread::class)->withTimestamps();
    }
}
