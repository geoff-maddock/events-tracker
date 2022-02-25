<?php

namespace App\Models;

use App\Filters\QueryFilter;
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
use Illuminate\Support\Str;

/**
 * App\Models\Thread.
 *
 * @property string                                                        $name
 * @property int                                                           $created_by
 * @property datetime                                                      $created_at
 * @property datetime                                                      $updated_at
 * @property int                                                           $id
 * @property int                                                           $forum_id
 * @property int|null                                                      $thread_category_id
 * @property string                                                        $slug
 * @property string|null                                                   $description
 * @property string                                                        $body
 * @property int                                                           $allow_html
 * @property int|null                                                      $visibility_id
 * @property int|null                                                      $recipient_id
 * @property int                                                           $sort_order
 * @property int                                                           $is_edittable
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Like[]   $likes
 * @property int                                                           $views
 * @property int                                                           $is_active
 * @property int|null                                                      $updated_by
 * @property string|null                                                   $locked_at
 * @property int|null                                                      $locked_by
 * @property int|null                                                      $event_id
 * @property \Illuminate\Database\Eloquent\Collection                      $tags;
 * @property \Illuminate\Database\Eloquent\Collection|Tag[]                $tags
 * @property \App\Models\User|null                                         $creator
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Entity[] $entities
 * @property int|null                                                      $entities_count
 * @property \App\Models\Event|null                                        $event
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Follow[] $follows
 * @property int|null                                                      $follows_count
 * @property \App\Models\Forum                                             $forum
 * @property mixed                                                         $entity_list
 * @property mixed                                                         $is_locked
 * @property mixed                                                         $last_post_at
 * @property mixed                                                         $post_count
 * @property mixed                                                         $tag_list
 * @property int|null                                                      $likes_count
 * @property \App\Models\User|null                                         $locker
 * @property \Illuminate\Database\Eloquent\Collection|Photo[]              $photos
 * @property int|null                                                      $photos_count
 * @property \Illuminate\Database\Eloquent\Collection|Post[]               $posts
 * @property int|null                                                      $posts_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Series[] $series
 * @property int|null                                                      $series_count
 * @property int|null                                                      $tags_count
 * @property \App\Models\ThreadCategory|null                               $threadCategory
 * @property \App\Models\User|null                                         $user
 * @property \App\Models\Visibility|null                                   $visibility
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Thread filter(\App\Filters\QueryFilter $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Thread newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Thread past()
 * @method static \Illuminate\Database\Eloquent\Builder|Thread query()
 * @method static \Illuminate\Database\Eloquent\Builder|Thread visible($user)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereAllowHtml($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereForumId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereIsEdittable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereLikes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereLockedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereLockedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereRecipientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereThreadCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereViews($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Thread whereVisibilityId($value)
 */
class Thread extends Eloquent
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($thread) {
            $thread->created_by = Auth::user() ? Auth::user()->id : 1;
            $thread->updated_by = Auth::user() ? Auth::user()->id : 1;
        });

        static::updating(function ($thread) {
            $thread->updated_by = Auth::user() ? Auth::user()->id : 1;
        });
    }

    protected $fillable = [
        'name',
        'description',
        'slug',
        'body',
        'thread_category_id',
        'visibility_id',
        'forum_id',
        'views',
        'event_id',
        'locked_at',
        'locked_by',
    ];

    protected $dates = ['created_at', 'updated_at'];

    public function scopeFilter(Builder $query, QueryFilter $filters): Builder
    {
        return $filters->apply($query);
    }

    public function path(): string
    {
        return '/threads/'.$this->id;
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('created_at', '<', Carbon::today()->startOfDay())
                        ->orderBy('start_at', 'desc');
    }

    /**
     * Returns visible threads.
     */
    public function scopeVisible(Builder $query, ?User $user): Builder
    {
        return $query->where(function ($query) use ($user) {
            $query->whereIn('visibility_id', [1, 2])
                ->where('created_by', '=', $user ? $user->id : null);
            // if logged in, can see guarded
            if ($user) {
                $query->orWhere('visibility_id', '=', 4);
            }
            $query->orWhere('visibility_id', '=', 3);

            return $query;
        });
    }

    /**
     * The posts that belong to the thread.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Add a post to a thread.
     */
    public function addPost(array $post): Post
    {
        return $this->posts()->create($post);
    }

    /**
     * Get the date of the last post.
     */
    public function getLastPostAtAttribute(): DateTime
    {
        $post = $this->posts()->orderBy('created_at', 'desc')->first();

        if (isset($post)) {
            return $post->created_at;
        }

        return $this->created_at;
    }

    /**
     * An thread is owned by a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * An thread is created by one user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Checks if the thread is followed by the user.
     *
     **/
    public function followedBy(User $user): ?Follow
    {
        return Follow::where('object_type', '=', 'thread')
            ->where('object_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->first();
    }

    /**
     * The follows that belong to the thread.
     */
    public function follows(): BelongsToMany
    {
        return $this->belongsToMany(Follow::class)->withTimestamps();
    }

    /**
     * Returns the users that follow the entity.
     *
     **/
    public function followers(): Collection
    {
        $users = User::join('follows', 'users.id', '=', 'follows.user_id')
        ->where('follows.object_type', 'thread')
        ->where('follows.object_id', $this->id)
        ->get();

        return $users;
    }

    /**
     * Checks if the thread is liked by the user.
     *
     **/
    public function likedBy(User $user): ?Like
    {
        return Like::where('object_type', '=', 'thread')
            ->where('object_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->first();
    }

    /**
     * The likes that belong to the thread.
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'object', 'object_type', 'object_id');
    }

    /**
     * Returns the users that like the entity.
     *
     * @return Collection $likes
     *
     **/
    public function likers()
    {
        $users = User::join('likes', 'users.id', '=', 'likes.user_id')
            ->where('likes.object_type', 'thread')
            ->where('likes.object_id', $this->id)
            ->get();

        return $users;
    }

    /**
     * An thread is owned by one forum.
     */
    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }

    /**
     * An thread is created by one user.
     */
    public function ownedBy(User $user): bool
    {
        return $this->created_by == $user->id;
    }

    /**
     * Checks if a thread was recent - thus edittable or deletable.
     */
    public function isRecent(): bool
    {
        $recent_hours = 24;

        // recency cut off date
        $recent_date = Carbon::parse('now')->subHours($recent_hours);

        $created_date = Carbon::parse($this->created_at);

        return ($created_date > $recent_date) ? true : false;
    }

    /**
     * An thread has one type.
     */
    public function threadCategory(): HasOne
    {
        return $this->hasOne(ThreadCategory::class, 'id', 'thread_category_id');
    }

    /**
     * Get all of the threads photos.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class)->withTimestamps();
    }

    /**
     * A thread has one visibility.
     */
    public function visibility(): HasOne
    {
        return $this->hasOne(Visibility::class, 'id', 'visibility_id');
    }

    /**
     * A thread has one or no locked by uses.
     */
    public function locker(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'locked_by');
    }

    /**
     * A thread has one series.
     */
    public function series(): BelongsToMany
    {
        return $this->belongsToMany(Series::class)->withTimestamps();
    }

    /**
     * The tags that belong to the thread.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * The entities that belong to the thread.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * A thread has one event at most.
     */
    public function event(): HasOne
    {
        return $this->hasOne(Event::class, 'id', 'event_id');
    }

    /**
     * Get the count of users attending this thread.
     */
    public function getPostCountAttribute(): int
    {
        $posts = $this->posts()->get();

        return count($posts);
    }

    /**
     * Get the locked status of the thread.
     */
    public function getIsLockedAttribute(): bool
    {
        $posts = $this->posts()->get();

        return (null == $this->locker) ? false : true;
    }

    /**
     * Get a list of tag ids associated with the thread.
     */
    public function getTagListAttribute(): array
    {
        return $this->tags->pluck('id')->all();
    }

    /**
     * Get a list of entity ids associated with the thread.
     */
    public function getEntityListAttribute(): array
    {
        return $this->entities->pluck('id')->all();
    }

    /**
     * Set the event attribute.
     */
    public function setEventIdAttribute(?int $value): void
    {
        if (!empty($value)) {
            $this->attributes['event_id'] = $value;
        } else {
            $this->attributes['event_id'] = null;
        }
    }

    /**
     * Create the slug from the name if none was passed.
     */
    public function setSlugAttribute(?string $value): void
    {
        // grab the name and slugify it
        if (!empty($value)) {
            $this->attributes['slug'] = $value;
        } else {
            $this->attributes['slug'] = Str::slug($this->name);
        }
    }

    /**
     * Set the name and some other side effects.
     */
    public function setNameAttribute(string $value): void
    {
        // grab the name and slugify it
        if (!empty($value)) {
            $this->attributes['name'] = $value;
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    /**
     * Set the thread category.
     */
    public function setThreadCategoryIdAttribute(?int $value): void
    {
        if (!empty($value)) {
            $this->attributes['thread_category_id'] = $value;
        } else {
            $this->attributes['thread_category_id'] = null;
        }
    }

    /**
     * Return a collection of threads with the passed tag.
     *
     **/
    public static function getByTag(string $tag): Builder
    {
        // get a list of threads that have the passed tag
        return self::whereHas('tags', function ($q) use ($tag) {
            $q->where('name', '=', ucfirst($tag));
        });
    }

    /**
     * Return a collection of threads with the passed series.
     *
     **/
    public static function getBySeries(string $series): Builder
    {
        // get a list of threads that have the passed series
        return self::whereHas('series', function ($q) use ($series) {
            $q->where('slug', '=', ucfirst($series));
        });
    }

    /**
     * Return a collection of threads with the passed thread category.
     *
     **/
    public static function getByCategory(string $slug): Builder
    {
        // get a list of threads that have the passed category
        return self::whereHas('threadCategory', function ($q) use ($slug) {
            $q->where('name', '=', $slug);
        })->orderBy('name', 'ASC');
    }

    /**
     * Return a collection of threads with the passed entity.
     *
     **/
    public static function getByEntity(string $slug): Builder
    {
        // get a list of threads that have the passed entity
        return self::whereHas('entities', function ($q) use ($slug) {
            $q->where('slug', '=', $slug);
        });
    }

    public function addPhoto(Photo $photo): void
    {
        $this->photos()->attach($photo->id);
    }

    /**
     * Return the flyer for this thread.
     *
     **/
    public function getFlyer(): ?Photo
    {
        // get a list of threads that start on the passed date
        return $this->photos()->first();
    }

    /**
     * Return the primary photo for this thread.
     *
     **/
    public function getPrimaryPhoto(): ?Photo
    {
        // gets the first photo related to this thread
        return $this->photos()->where('photos.is_primary', '=', '1')->first();
    }
}
