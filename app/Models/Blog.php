<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

/**
 * App\Models\Blog
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $body
 * @property int|null $menu_id
 * @property int|null $content_type_id
 * @property int|null $visibility_id
 * @property int $sort_order
 * @property int $is_active
 * @property int $is_admin
 * @property int $allow_html
 * @property int $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ContentType|null $contentType
 * @property-read \App\Models\User $creator
 * @property-read Collection|\App\Models\Entity[] $entities
 * @property-read int|null $entities_count
 * @property-read mixed $entity_list
 * @property-read mixed $tag_list
 * @property-read Collection|\App\Models\Like[] $likes
 * @property-read int|null $likes_count
 * @property-read \App\Models\Menu|null $menu
 * @property-read Collection|\App\Models\Photo[] $photos
 * @property-read int|null $photos_count
 * @property-read Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Visibility|null $visibility
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

    /**
     * @var array
     *
     **/
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

    // building filter
    public function scopeFilter($query, QueryFilter $filters)
    {
        return $filters->apply($query);
    }

    public function path()
    {
        return '/blog/' . $this->id;
    }

    public function scopePast($query)
    {
        $query->where('created_at', '<', Carbon::today()->startOfDay())
                        ->orderBy('start_at', 'desc');
    }

    /**
     * Returns visible blogs.
     */
    public function scopeVisible($query, $user)
    {
        $public = Visibility::where('name', '=', 'Public')->first();

        $query->where('visibility_id', '=', $public ? $public->id : null)->orWhere('created_by', '=', ($user ? $user->id : null));
    }

    /**
     * An blog is owned by a user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * The likes that belong to the blog.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function likes()
    {
        return $this->morphMany('App\Models\Like', 'object', 'object_type', 'object_id');
    }

    /**
     * An blog is owned by a menu.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function menu()
    {
        return $this->belongsTo('App\Models\Menu', 'menu_id');
    }

    /**
     * An blog is created by one user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * An blog is created by one user.
     *
     * @ param User $user
     *
     * @ return boolean
     */
    public function ownedBy(User $user)
    {
        return $this->created_by == $user->id;
    }

    /**
     * Get all of the blogs photos
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
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Tag')->withTimestamps();
    }

    /**
     * The entities that belong to the blog.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * Get a list of tag ids associated with the blog.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getTagListAttribute()
    {
        return $this->tags->pluck('id')->all();
    }

    /**
     * Get a list of entity ids associated with the blog.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getEntityListAttribute()
    {
        return $this->entities->pluck('id')->all();
    }

    /**
     * Return a collection of blogs with the passed tag.
     *
     * @return Collection $blogs
     *
     **/
    public static function getByTag($tag)
    {
        // get a list of blogs that have the passed tag
        $blogs = self::whereHas('tags', function ($q) use ($tag) {
            $q->where('name', '=', ucfirst($tag));
        });

        return $blogs;
    }

    /**
     * Return a collection of blogs with the passed entity.
     *
     * @return Collection $blogs
     *
     **/
    public static function getByEntity($slug)
    {
        // get a list of blogs that have the passed entity
        $blogs = self::whereHas('entities', function ($q) use ($slug) {
            $q->where('slug', '=', $slug);
        });

        return $blogs;
    }

    public function addPhoto(Photo $photo): void
    {
        $this->photos()->attach($photo->id);
    }

    /**
     * Return the primary photo for this blog.
     *
     * @return Photo $photo
     *
     **/
    public function getPrimaryPhoto()
    {
        // gets the first photo related to this blog
        $primary = $this->photos()->where('photos.is_primary', '=', '1')->first();

        return $primary;
    }

    /**
     * Checks if a blog was recent - thus edittable or deletable.
     *
     * @ return boolean
     */
    public function isRecent()
    {
        $recent_hours = 24;

        // recency cut off date
        $recent_date = Carbon::parse('now')->subHours($recent_hours);

        $created_date = Carbon::parse($this->created_at);

        return ($created_date > $recent_date) ? true : false;
    }

    /**
     * Determine if the blog was just published a moment ago.
     *
     * @return bool
     */
    public function wasJustPublished()
    {
        return $this->created_at->gt(Carbon::now()->subMinute());
    }

    /**
     * Fetch all mentioned users within the blog's body.
     *
     * @return array
     */
    public function mentionedUsers()
    {
        preg_match_all('/@([\w\-]+)/', $this->body, $matches);

        return $matches[1];
    }

    /**
     * Checks if the blog is liked by the user.
     *
     * @return Collection $likes
     *
     **/
    public function likedBy($user)
    {
        $response = Like::where('object_type', '=', 'blog')
            ->where('object_id', '=', $this->id)
            ->where('user_id', '=', $user->id)
            ->first();
        // return any like instances

        return $response;
    }

    /**
     * Returns the users that like the blog.
     *
     * @return Collection $likes
     *
     **/
    public function likers()
    {
        $users = User::join('likes', 'users.id', '=', 'likes.user_id')
            ->where('likes.object_type', 'blog')
            ->where('likes.object_id', $this->id)
            ->get();

        return $users;
    }

    /**
     * Returns html events.
     */
    public function scopeHtml($query, $user)
    {
        $htmlType = ContentType::where('name', '=', 'HTML')->first();

        $query->where('content_type_id', '=', $htmlType ? $htmlType->id : null);
    }

    /**
     * An event has one contentType.
     */
    public function contentType()
    {
        return $this->hasOne('App\Models\ContentType', 'id', 'content_type_id');
    }
}
