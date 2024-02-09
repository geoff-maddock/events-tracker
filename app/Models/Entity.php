<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Str;

/**
 * App\Models\Entity.
 *
 * @property int                                                             $id
 * @property string                                                          $name
 * @property string                                                          $slug
 * @property string                                                          $short
 * @property string                                                          $description
 * @property int|null                                                        $entity_type_id
 * @property int|null                                                        $entity_status_id
 * @property int                                                             $created_by
 * @property int|null                                                        $updated_by
 * @property \Illuminate\Support\Carbon                                      $started_at
 * @property \Illuminate\Support\Carbon                                      $created_at
 * @property \Illuminate\Support\Carbon                                      $updated_at
 * @property string|null                                                     $facebook_username
 * @property string|null                                                     $twitter_username
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Alias[]    $aliases
 * @property int|null                                                        $aliases_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[]  $comments
 * @property int|null                                                        $comments_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Contact[]  $contacts
 * @property int|null                                                        $contacts_count
 * @property \App\Models\EntityStatus|null                                   $entityStatus
 * @property \App\Models\EntityType|null                                     $entityType
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Event[]    $events
 * @property int|null                                                        $events_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Follow[]   $follows
 * @property int|null                                                        $follows_count
 * @property mixed                                                           $alias_list
 * @property mixed                                                           $role_list
 * @property mixed                                                           $tag_list
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Like[]     $likes
 * @property int|null                                                        $likes_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Link[]     $links
 * @property int|null                                                        $links_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Location[] $locations
 * @property int|null                                                        $locations_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Photo[]    $photos
 * @property int|null                                                        $photos_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Role[]     $roles
 * @property int|null                                                        $roles_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Series[]   $series
 * @property int|null                                                        $series_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[]      $tags
 * @property int|null                                                        $tags_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Thread[]   $threads
 * @property int|null                                                        $threads_count
 * @property \App\Models\User                                                $user
 *
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
    use Notifiable;

    protected $fillable = [
        'name', 'slug', 'short', 'description', 'entity_type_id', 'entity_status_id', 'entity_address_id', 'facebook_username', 'twitter_username', 'created_by', 'started_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'started_at' => 'datetime',
    ];

    protected $attributes = [
        'description' => '',
    ];


    public static function boot()
    {
        parent::boot();

        // TODO Fix the default after I resolve user setup in API
        static::creating(function ($entity) {
            $entity->created_by = Auth::user() ? Auth::user()->id : 1;
            $entity->updated_by = Auth::user() ? Auth::user()->id : 1;
        });

        static::updating(function ($entity) {
            $entity->updated_by = Auth::user() ? Auth::user()->id : 1;
        });
    }

    
    /**
     * Return a collection of entities with the role venue.
     * @return Builder<Entity>
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
     * Return a collection of entities with the role promoter.
     * @return Builder<Entity>
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
     * @return Builder<Entity>
     **/
    public static function getByRole(string $role): Builder
    {
        // get a list of entities that have the passed role
        $entities = self::whereHas('roles', function ($q) use ($role) {
            $q->where('slug', '=', strtolower($role));
        })->orderBy('name', 'ASC');

        return $entities;
    }

    /**
     * Return a collection of entities with the passed tag.
     * @return Builder<Entity>
     **/
    public static function getByTag(string $tag): Builder
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
     **/
    public static function getBySlug(string $slug): Builder
    {
        // get a single entity by slug
        $entity = self::where('slug', $slug);

        return $entity;
    }

    /**
     * Return a collection of entities with the passed type.
     *
     **/
    public static function getByType(string $type): Builder
    {
        // get a list of entities that have the passed role
        $entities = self::whereHas('entity_type', function ($q) use ($type) {
            $q->where('name', '=', ucfirst($type));
        })->orderBy('name', 'ASC');

        return $entities;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get all of the entities comments.
     */
    public function comments(): MorphMany
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
     * Returns entities created by the user.
     *
     * @ param User $user
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('created_by', '=', $user->id);
    }

    /**
     * Returns entities that are promoters.
     */
    public function scopePromoter(Builder $query, string $type): Builder
    {
        $type = EntityType::where('name', '=', $type)->first();

        return $query->where('entity_type_id', '=', $type->id);
    }

    /**
     * Returns active entities.
     */
    public function scopeActive(Builder $query): Builder
    {
        $status = EntityStatus::where('name', '=', 'Active')->first();

        return $query->where('entity_status_id', '=', $status ? $status->id : null);
    }

    // /**
    //  * Returns visible entities.
    //  * @return Builder<Entity>
    //  */
    // public function scopeVisible(Builder $query, ?User $user): Builder
    // {
    //     $query = $query->active();
    //     return $query->where(function ($query) use ($user) {

    //         // if logged in, can see guarded
    //         if ($user) {
    //             $query->orWhere('created_by', '=', ($this->user ? $this->user->id : null));
    //         }

    //         return $query;
    //     });
    // }

    /**
     * An entity is owned by a user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function allOrdered(): Collection
    {
        return static::orderBy('name', 'ASC')->get();
    }

    /**
     * The contacts that belong to the entity.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    /**
     * The contacts that belong to the entity.
     */
    public function series(): BelongsToMany
    {
        return $this->belongsToMany(Series::class);
    }

    /**
     * The links that belong to the entity.
     */
    public function links(): BelongsToMany
    {
        return $this->belongsToMany(Link::class);
    }

    /**
     * An entity has one entity type.
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityType::class);
    }

    /**
     * An entity has one status.
     */
    public function entityStatus(): BelongsTo
    {
        return $this->belongsTo(EntityStatus::class);
    }

    /**
     * The tags that belong to the entity.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * The aliases that belong to the entity.
     */
    public function aliases(): BelongsToMany
    {
        return $this->belongsToMany(Alias::class)->withTimestamps();
    }

    /**
     * The follows that belong to the entity.
     */
    public function follows(): MorphMany
    {
        return $this->morphMany(Follow::class, 'object', 'object_type', 'object_id');
    }

    /**
     * The likes that belong to the entity.
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'object', 'object_type', 'object_id');
    }

    /**
     * If there is a future event, return it.
     */
    public function futureEvents(?int $rpp = null): LengthAwarePaginator
    {
        return $this->events()->distinct()
            ->where('start_at', '>=', Carbon::now())
            ->orderBy('start_at', 'ASC')
            ->paginate($rpp);
    }

    /**
     * If there is a past event, return it.
     */
    public function pastEvents(int $rpp = null): LengthAwarePaginator
    {
        return $this->events()->distinct()
            ->where('start_at', '<', Carbon::now())
            ->orderBy('start_at', 'DESC')
            ->paginate($rpp);
    }

    /**
     * The events that belong to the entity.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->with('visibility', 'venue','entities')->withTimestamps();
    }

    /**
     * Return any events that match today for the start date.
     */
    public function todaysEvents(): Collection
    {
        return $this->events()->whereDate('start_at', '=', Carbon::today()->toDateString())->orderBy('start_at', 'ASC')->get();
    }

    /**
     * Return the first primary link.
     */
    public function primaryLink(): ?Link
    {
        return $this->links()->where('is_primary', '=', 1)->orderBy('created_at', 'ASC')->first();
    }

    /**
     * Return the first bandcamp link.
     */
    public function getBandcampLinkAttribute(): ?Link
    {
        return $this->links()->where('url', 'LIKE', '%bandcamp%')->orderBy('created_at', 'ASC')->first();
    }

    /**
     * Return the first soundcloud link.
     */
    public function getSoundcloudLinkAttribute(): ?Link
    {
        return $this->links()->where('url', 'LIKE', '%soundcloud%')->orderBy('created_at', 'ASC')->first();
    }


    /**
     * Events that occurred at this venue.
     */
    public function eventsAtVenue(): Collection
    {
        return Event::where('venue_id', $this->id)->orderBy('start_at', 'ASC')->get();
    }

    /**
     * Get a list of tag ids associated with the entity.
     */
    public function getTagListAttribute(): array
    {
        return $this->tags->pluck('id')->all();
    }

    /**
     * Get a list of alias ids associated with the entity.
     */
    public function getAliasListAttribute(): array
    {
        return $this->aliases->pluck('id')->all();
    }

    /**
     * Get a list of role ids associated with the entity.
     */
    public function getRoleListAttribute(): array
    {
        return $this->roles->pluck('id')->all();
    }

    public function addPhoto(Photo $photo): void
    {
        $this->photos()->attach($photo->id);
    }

    /**
     * Get all of the entities photos.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class)->withTimestamps();
    }

    /**
     * Return the primary photo for this entity.
     *
     **/
    public function getPrimaryPhoto(): ?Photo
    {
        // get a list of events that start on the passed date
        $primary = $this->photos()->where('photos.is_primary', '=', '1')->first();

        return $primary;
    }

    /**
     * Return the primary location for this entity.
     *
     **/
    public function getPrimaryLocation(): ?Model
    {
        // get a list of events that start on the passed date
        $primary = $this->locations->first();

        return $primary;
    }

    /**
     * The locations that belong to the entity.
     *
     * @phpstan-return HasMany<Location>
     */
    public function locations(): ?HasMany
    {
        return $this->hasMany(Location::class)->with('visibility');
    }

    /**
     * Return the primary location address.
     *
     **/
    public function getPrimaryLocationAddress(bool $signedIn = null): string
    {
        $address = '';

        // get a list of events that start on the passed date
        $primary = $this->locations->first();

        // @phpstan-ignore-next-line
        if ($primary && ('Guarded' != $primary->visibility->name || ('Guarded' == $primary->visibility->name) && $signedIn)) {
            // @phpstan-ignore-next-line
            $address .= $primary->address_one.' ';
            // @phpstan-ignore-next-line
            $address .= $primary->city;
        }

        return $address;
    }

    /**
     * Return the primary location address.
     *
     **/
    public function getPrimaryLocationMap(): ?string
    {
        // get a list of events that start on the passed date
        $primary = $this->locations->first();

        if ($primary) {
            return $primary->map_url;
        }

        return '';
    }

    /**
     * Checks if the entity is followed by the user.
     *
     **/
    public function followedBy(?User $user): ?Follow
    {
        if (!$user) {
            return null;
        }

        return Follow::where('object_type', '=', 'entity')
            ->where('object_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->first();
    }

    /**
     * Returns the users that follow the entity.
     *
     * @return Collection $follows
     *
     **/
    public function followers(): Collection
    {
        return User::with('profile')->join('follows', 'users.id', '=', 'follows.user_id')
            ->where('follows.object_type', 'entity')
            ->where('follows.object_id', $this->id)
            ->get('users.*');
    }

    /**
     * Checks if the entity is liked by the user.
     *
     **/
    public function likedBy(User $user): Like
    {
        return Like::where('object_type', '=', 'entity')
            ->where('object_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->first();
    }

    /**
     * Returns the users that like the entity.
     *
     **/
    public function likers(): Collection
    {
        return User::join('likes', 'users.id', '=', 'follows.user_id')
            ->where('likes.object_type', 'entity')
            ->where('likes.object_id', $this->id)
            ->get();
    }

    public function hasRole(string $role): int
    {
        return $this->roles()->where('name', $role)->count();
    }

    /**
     * The roles that belong to the entity.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Get the threads that belong to the series.
     */
    public function threads(): BelongsToMany
    {
        return $this->belongsToMany(Thread::class)->withTimestamps();
    }

    public function getTitleFormat(): string
    {
        $format = $this->name;

        if (count($this->roles) > 0) {
            $format .= ' - ';
            foreach ($this->roles as $role) {
                $format .= $role->name.', ';
            }
            $format = substr($format, 0, -2);
        }

        return $format;
    }

    // Format the entity to post as a tweet
    public function getBriefFormat(): string
    {
        // max length 280 chars
        // URLs count as 23 chars

        // add name and type
        $format = $this->name.' | '.$this->entityType->name.' | ';
        $format .= $this->short;

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
            $format .= ' https://arcane.city/entities/'.$this->slug;
        }

        // add the primary link
        if (count($this->links) > 0) {
            foreach ($this->links as $link) {
                // if there are at least 23 chars remaining, add primary link
                if (strlen($format) < 258) {
                    $format .= ' '.$link->url;
                }
            }
        }

        // add the primary link
        if ($this->futureEvents()->total() > 0) {
            $event = $this->futureEvents()->items()[0];
            $start = $event->start_at->format('m/d');
            $format .= ' Next: '.$start.' '.$event->name.' at '.$event->venue->name;
        }

        // only return the first 280 chars
        return substr($format, 0, 280);
    }
}
