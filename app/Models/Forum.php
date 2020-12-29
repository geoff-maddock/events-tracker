<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Forum
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int|null $visibility_id
 * @property int $sort_order
 * @property int $is_active
 * @property int $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $creator
 * @property-read int|null $threads_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Thread[] $threads
 * @property-read \App\Models\Thread|null $threadsCount
 * @property-read User $user
 * @property-read \App\Models\Visibility|null $visibility
 * @method static \Illuminate\Database\Eloquent\Builder|Forum filter(\App\Filters\QueryFilter $filters)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Forum newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Forum query()
 * @method static \Illuminate\Database\Eloquent\Builder|Forum visible($user)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Forum whereVisibilityId($value)
 * @mixin \Eloquent
 */
class Forum extends Eloquent
{
    use HasFactory;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            $event->created_by = Auth::user() ? Auth::user()->id : 1;
            $event->updated_by = Auth::user() ? Auth::user()->id : 1;
        });

        static::updating(function ($event) {
            $event->updated_by = Auth::user() ? Auth::user()->id : 1;
        });
    }

    /**
     * @var array
     *
     **/
    protected $fillable = [
        'name',
        'slug',
        'short',
        'description',
        'visibility_id',
    ];

    protected $dates = ['created_at', 'updated_at'];

    public function scopeFilter($query, QueryFilter $filters)
    {
        return $filters->apply($query);
    }

    /**
     * Returns visible events.
     */
    public function scopeVisible($query, $user)
    {
        $public = Visibility::where('name', '=', 'Public')->first();

        $query->where('visibility_id', '=', $public ? $public->id : null)->orWhere('created_by', '=', ($user ? $user->id : null));
    }

    /**
     * An event is owned by a user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * An event is created by one user.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * An thread is created by one user.
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
     * An event has one visibility.
     */
    public function visibility()
    {
        return $this->hasOne(Visibility::class, 'id', 'visibility_id');
    }

    /**
     * The threads that belong to the forum.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function threads()
    {
        return $this->hasMany(Thread::class);
    }

    // Post model
    public function threadsCount()
    {
        return $this->hasOne(Thread::class)
        ->selectRaw('forum_id, count(*) as aggregate')
        ->groupBy('forum_id');
    }

    public function getThreadsCountAttribute()
    {
        // if relation is not loaded already, let's do it first
        if (!array_key_exists('threadsCount', $this->relations)) {
            $this->load('threadsCount');
        }

        $related = $this->getRelation('threadsCount');

        // then return the count directly
        return ($related) ? (int) $related->aggregate : 0;
    }
}
