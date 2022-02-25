<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * App\Models\Post.
 *
 * @property int                                                           $id
 * @property int                                                           $thread_id
 * @property string|null                                                   $name
 * @property string|null                                                   $slug
 * @property string|null                                                   $description
 * @property string                                                        $body
 * @property int                                                           $allow_html
 * @property int|null                                                      $content_type_id
 * @property int|null                                                      $visibility_id
 * @property int|null                                                      $recipient_id
 * @property int|null                                                      $reply_to
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Like[]   $likes
 * @property int                                                           $views
 * @property int                                                           $is_active
 * @property int|null                                                      $created_by
 * @property int|null                                                      $updated_by
 * @property \Illuminate\Support\Carbon                                    $created_at
 * @property \Illuminate\Support\Carbon                                    $updated_at
 * @property \App\Models\User|null                                         $creator
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Entity[] $entities
 * @property int|null                                                      $entities_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Event[]  $events
 * @property int|null                                                      $events_count
 * @property mixed                                                         $entity_list
 * @property mixed                                                         $tag_list
 * @property int|null                                                      $likes_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Photo[]  $photos
 * @property int|null                                                      $photos_count
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[]    $tags
 * @property int|null                                                      $tags_count
 * @property \App\Models\Thread                                            $thread
 * @property \App\Models\User|null                                         $user
 * @property Visibility|null                                               $visibility
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Post filter(\App\Filters\QueryFilter $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Post past()
 * @method static \Illuminate\Database\Eloquent\Builder|Post query()
 * @method static \Illuminate\Database\Eloquent\Builder|Post visible($user)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereAllowHtml($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereContentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereLikes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereRecipientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereReplyTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereViews($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Post whereVisibilityId($value)
 * @mixin \Eloquent
 */
class Post extends Eloquent
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            //$post->created_by = Auth::user() ? Auth::user()->id : 1;
            $post->updated_by = Auth::user() ? Auth::user()->id : 1;
        });

        static::updating(function ($post) {
            $post->updated_by = Auth::user() ? Auth::user()->id : 1;
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'visibility_id',
        'body',
        'thread_id',
        'created_by',
    ];

    protected $guarded = [];

    protected $dates = ['created_at', 'updated_at'];

    public function __toString()
    {
        return (string) $this->body;
    }

    public function scopeFilter(Builder $query, QueryFilter $filters): Builder
    {
        return $filters->apply($query);
    }

    public function path(): string
    {
        return '/post/'.$this->id;
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('created_at', '<', Carbon::today()->startOfDay())
                        ->orderBy('start_at', 'desc');
    }

    /**
     * Returns visible posts.
     */
    public function scopeVisible(Builder $query, ?User $user): Builder
    {
        $public = Visibility::where('name', '=', 'Public')->first();

        return $query->where('visibility_id', '=', $public ? $public->id : null)->orWhere('created_by', '=', ($user ? $user->id : null));
    }

    /**
     * An post is owned by a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The likes that belong to the post.
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'object', 'object_type', 'object_id');
    }

    /**
     * An post is owned by a thread.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class, 'thread_id');
    }

    /**
     * An post is created by one user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * An post is created by one user.
     */
    public function ownedBy(User $user): bool
    {
        return $this->created_by == $user->id;
    }

    /**
     * Get all of the posts photos.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class)->withTimestamps();
    }

    /**
     * An post has one visibility.
     */
    public function visibility(): HasOne
    {
        return $this->hasOne(Visibility::class, 'id', 'visibility_id');
    }

    /**
     * The tags that belong to the post.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * The entities that belong to the post.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * The events that belong to the post.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    /**
     * Get a list of tag ids associated with the post.
     */
    public function getTagListAttribute(): array
    {
        return $this->tags->pluck('id')->all();
    }

    /**
     * Get a list of entity ids associated with the post.
     */
    public function getEntityListAttribute(): array
    {
        return $this->entities->pluck('id')->all();
    }

    /**
     * Return a collection of posts with the passed tag.
     *
     **/
    public static function getByTag(string $tag): Builder
    {
        // get a list of posts that have the passed tag
        return self::whereHas('tags', function ($q) use ($tag) {
            $q->where('name', '=', ucfirst($tag));
        });
    }

    /**
     * Return a collection of posts with the passed entity.
     *
     **/
    public static function getByEntity(string $slug): Builder
    {
        // get a list of posts that have the passed entity
        return self::whereHas('entities', function ($q) use ($slug) {
            $q->where('slug', '=', $slug);
        });
    }

    public function addPhoto(Photo $photo): void
    {
        $this->photos()->attach($photo->id);
    }

    /**
     * Return the primary photo for this post.
     *
     **/
    public function getPrimaryPhoto(): Photo
    {
        // gets the first photo related to this post
        return $this->photos()->where('photos.is_primary', '=', '1')->first();
    }

    /**
     * Checks if a post was recent - thus edittable or deletable.
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
     * Determine if the post was just published a moment ago.
     */
    public function wasJustPublished(): bool
    {
        return $this->created_at->gt(Carbon::now()->subMinute());
    }

    /**
     * Fetch all mentioned users within the post's body.
     */
    public function mentionedUsers(): array
    {
        preg_match_all('/@([\w\-]+)/', $this->body, $matches);

        return $matches[1];
    }

    /**
     * Checks if the post is liked by the user.
     *
     **/
    public function likedBy(User $user): ?Like
    {
        return Like::where('object_type', '=', 'post')
            ->where('object_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->first();
    }

    /**
     * Returns the users that like the post.
     *
     **/
    public function likers(): Collection
    {
        return User::join('likes', 'users.id', '=', 'likes.user_id')
            ->where('likes.object_type', 'post')
            ->where('likes.object_id', $this->id)
            ->get();
    }
}
