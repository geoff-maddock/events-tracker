<?php

namespace App\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Filters\TagFilters;

/**
 * @property int      $id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string   $name
 * @property string   $slug
 * @property string|null $description
 * @property TagType  $tagType
 * @property int|null $tag_type_id
 * @property DateTime $created_at
 * @property int|null $popularity_score
 */
class Tag extends Eloquent
{
    use HasFactory;

    protected $fillable = [
        'name', 'tag_type_id', 'slug', 'description',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    /**
     * Get the threads that belong to the tag.
     */
    public function threads(): BelongsToMany
    {
        return $this->belongsToMany(Thread::class)->withTimestamps();
    }

    /**
     * Get the series that belong to the tag.
     */
    public function series(): BelongsToMany
    {
        return $this->belongsToMany(Series::class)->withTimestamps();
    }

    /**
     * Get the events that belong to the tag.
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    /**
     * Get the entities that belong to the tag.
     */
    public function entities(): BelongsToMany
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * Adds a grid_thumbnail subquery select — returns the thumbnail path of the most recent
     * public event photo, falling back to the most recently created entity photo.
     */
    public function scopeWithGridThumbnail(Builder $query): Builder
    {
        return $query->select('tags.*')->addSelect(DB::raw('
            COALESCE(
                (SELECT p.thumbnail
                 FROM photos p
                 JOIN event_photo ep ON p.id = ep.photo_id
                 JOIN events e ON e.id = ep.event_id
                 JOIN event_tag et ON e.id = et.event_id
                 WHERE et.tag_id = tags.id
                   AND p.is_primary = 1
                   AND e.visibility_id = ' . Visibility::VISIBILITY_PUBLIC . '
                 ORDER BY e.start_at DESC
                 LIMIT 1),
                (SELECT p.thumbnail
                 FROM photos p
                 JOIN entity_photo eph ON p.id = eph.photo_id
                 JOIN entities en ON en.id = eph.entity_id
                 JOIN entity_tag etag ON en.id = etag.entity_id
                 WHERE etag.tag_id = tags.id
                   AND p.is_primary = 1
                 ORDER BY en.created_at DESC
                 LIMIT 1)
            ) as grid_thumbnail
        '));
    }

    /**
     * A tag has one type.
     */
    public function tagType(): BelongsTo
    {
        return $this->belongsTo(TagType::class);
    }

    /**
     * Checks if the tag is followed by the user.
     *
     **/
    public function followedBy(User $user): ?Follow
    {
        $response = Follow::where('object_type', '=', 'tag')
        ->where('object_id', '=', $this->id)
        ->where('user_id', '=', $user->id)
        ->with('user')
        ->first();

        return $response;
    }

    /**
     * Returns the users that follow the tag.
     *
     **/
    public function followers(): Collection
    {
        return User::leftJoin('follows', 'users.id', '=', 'follows.user_id')
        ->where('follows.object_type', 'tag')
        ->where('follows.object_id', $this->id)
        ->get('users.*');
    }

    /**
     * The follows that belong to the entity.
     */
    public function follows(): MorphMany
    {
        return $this->morphMany(Follow::class, 'object', 'object_type', 'object_id');
    }

    /**
     * If there is a future event, return it.
     */
    public function futureEvents(): Collection
    {
        $events = $this->events()->where('start_at', '>=', Carbon::now())->orderBy('start_at', 'ASC')->get();

        return $events;
    }

    /**
     * Return any events that match today for the start date.
     * 
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event>
     */
    public function todaysEvents(): Collection
    {
        $events = $this->events()->whereDate('start_at', '=', Carbon::today()->toDateString())->orderBy('start_at', 'ASC')->get();

        return $events;
    }

    /**
     * Get all the other tags on events that use this tag.
     */
    public function relatedTags(): array
    {
        $total = [];

        $events = $this->events()->with('tags')->get();
        foreach ($events as $event) {
            foreach ($event->tags as $tag) {
                if ($tag->name == $this->name) {
                    continue;
                }
                if (isset($total[$tag->name])) {
                    ++$total[$tag->name];
                } else {
                    $total[$tag->name] = 1;
                }
            }
        }
        arsort($total);

        return array_slice($total, 0, 5);
    }

    /**
     * Apply TagFilters to the query.
     */
    public function scopeFilter(Builder $query, TagFilters $filters): Builder
    {
        return $filters->apply($query);
    }
}
