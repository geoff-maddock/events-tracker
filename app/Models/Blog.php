<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * App\Models\Blog.
 *
 * @property int                             $id
 * @property string                          $name
 * @property string                          $slug
 * @property string                          $body
 * @property int|null                        $menu_id
 * @property int|null                        $content_type_id
 * @property int|null                        $visibility_id
 * @property int                             $sort_order
 * @property int                             $is_active
 * @property int                             $is_admin
 * @property int                             $allow_html
 * @property int                             $created_by
 * @property int|null                        $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Models\ContentType|null    $contentType
 * @property \App\Models\User                $creator
 * @property Collection|\App\Models\Entity[] $entities
 * @property int|null                        $entities_count
 * @property mixed                           $entity_list
 * @property mixed                           $tag_list
 * @property Collection|\App\Models\Like[]   $likes
 * @property int|null                        $likes_count
 * @property \App\Models\Menu|null           $menu
 * @property Collection|\App\Models\Photo[]  $photos
 * @property int|null                        $photos_count
 * @property Collection|\App\Models\Tag[]    $tags
 * @property int|null                        $tags_count
 * @property \App\Models\User                $user
 * @property \App\Models\Visibility|null     $visibility
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Blog filter(\App\Filters\QueryFilter $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog html($user)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Blog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Blog past()
 * @method static \Illuminate\Database\Eloquent\Builder|Blog query()
 * @method static \Illuminate\Database\Eloquent\Builder|Blog visible($user)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereAllowHtml($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereContentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereMenuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Blog whereVisibilityId($value)
 * @mixin \Eloquent
 */
class Blog extends Eloquent
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($blog) {
            //$blog->created_by = Auth::user() ? Auth::user()->id : 1;
            $blog->updated_by = Auth::user() ? Auth::user()->id : 1;
        });

        static::updating(function ($blog) {
            $blog->updated_by = Auth::user() ? Auth::user()->id : 1;
        });
    }

    protected $attributes = [
        'sort_order' => 0,
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'visibility_id',
        'content_type_id',
        'body',
        'menu_id',
        'sort_order',
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
        return '/blog/'.$this->id;
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('created_at', '<', Carbon::today()->startOfDay())
                        ->orderBy('start_at', 'desc');
    }

    /**
     * Returns visible blogs.
     */
    public function scopeVisible(Builder $query, ?User $user): Builder
    {
        $public = Visibility::where('name', '=', 'Public')->first();

        return $query->where('visibility_id', '=', $public ? $public->id : null)->orWhere('created_by', '=', ($user ? $user->id : null));
    }

    /**
     * An blog is owned by a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * The likes that belong to the blog.
     */
    public function likes(): MorphMany
    {
        return $this->morphMany('App\Models\Like', 'object', 'object_type', 'object_id');
    }

    /**
     * An blog is owned by a menu.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo('App\Models\Menu', 'menu_id');
    }

    /**
     * An blog is created by one user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * An blog is created by one user.
     */
    public function ownedBy(User $user): bool
    {
        return $this->created_by == $user->id;
    }

    /**
     * Get all of the blogs photos.
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Photo')->withTimestamps();
    }

    /**
     * An blog has one visibility.
     */
    public function visibility(): HasOne
    {
        return $this->hasOne('App\Models\Visibility', 'id', 'visibility_id');
    }

    /**
     * The tags that belong to the blog.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Tag')->withTimestamps();
    }

    /**
     * The entities that belong to the blog.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * Get a list of tag ids associated with the blog.
     */
    public function getTagListAttribute(): array
    {
        return $this->tags->pluck('id')->all();
    }

    /**
     * Get a list of entity ids associated with the blog.
     */
    public function getEntityListAttribute(): array
    {
        return $this->entities->pluck('id')->all();
    }

    /**
     * Return a collection of blogs with the passed tag.
     *
     **/
    public static function getByTag(string $tag): Builder
    {
        // get a list of blogs that have the passed tag
        return self::whereHas('tags', function ($q) use ($tag) {
            $q->where('name', '=', ucfirst($tag));
        });
    }

    /**
     * Return a collection of blogs with the passed entity.
     *
     **/
    public static function getByEntity(string $slug): Builder
    {
        // get a list of blogs that have the passed entity
        return self::whereHas('entities', function ($q) use ($slug) {
            $q->where('slug', '=', $slug);
        });
    }

    public function addPhoto(Photo $photo): void
    {
        $this->photos()->attach($photo->id);
    }

    /**
     * Return the primary photo for this blog.
     *
     **/
    public function getPrimaryPhoto(): ?Photo
    {
        // gets the first photo related to this blog
        return $this->photos()->where('photos.is_primary', '=', '1')->first();
    }

    /**
     * Checks if a blog was recent - thus edittable or deletable.
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
     * Determine if the blog was just published a moment ago.
     */
    public function wasJustPublished(): bool
    {
        return $this->created_at->gt(Carbon::now()->subMinute());
    }

    /**
     * Fetch all mentioned users within the blog's body.
     */
    public function mentionedUsers(): array
    {
        preg_match_all('/@([\w\-]+)/', $this->body, $matches);

        return $matches[1];
    }

    /**
     * Checks if the blog is liked by the user.
     *
     **/
    public function likedBy(User $user): Like
    {
        return Like::where('object_type', '=', 'blog')
            ->where('object_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->first();
    }

    /**
     * Returns the users that like the blog.
     *
     **/
    public function likers(): Collection
    {
        return User::join('likes', 'users.id', '=', 'likes.user_id')
            ->where('likes.object_type', 'blog')
            ->where('likes.object_id', $this->id)
            ->get();
    }

    /**
     * Returns html events.
     */
    public function scopeHtml(Builder $query): Builder
    {
        $htmlType = ContentType::where('name', '=', 'HTML')->first();

        return $query->where('content_type_id', '=', $htmlType ? $htmlType->id : null);
    }

    /**
     * An event has one contentType.
     */
    public function contentType(): HasOne
    {
        return $this->hasOne('App\Models\ContentType', 'id', 'content_type_id');
    }
}
