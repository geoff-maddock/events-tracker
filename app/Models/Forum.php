<?php

namespace App\Models;

use App\Filters\QueryFilter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

class Forum extends Eloquent
{
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
