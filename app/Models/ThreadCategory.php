<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;

class ThreadCategory extends Eloquent
{
    use HasFactory;

    /**
     * @var Array
     *
     **/
    protected $fillable = [
        'name',
        'forum_id'
    ];

    /**
     * Additional fields to treat as Carbon instances.
     *
     * @var array
     */
    protected $dates = [];

    /**
     * A thread category can have many threads
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function threads()
    {
        return $this->hasMany(Thread::class);
    }

    /**
     * An thread is owned by one forum.
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function forum()
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }

    public function getRouteKeyName()
    {
        return 'id';
    }
}
