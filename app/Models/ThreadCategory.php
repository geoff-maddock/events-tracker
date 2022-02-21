<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThreadCategory extends Eloquent
{
    use HasFactory;

    /**
     * @var array
     *
     **/
    protected $fillable = [
        'name',
        'forum_id',
    ];

    /**
     * Additional fields to treat as Carbon instances.
     *
     * @var array
     */
    protected $dates = [];

    /**
     * A thread category can have many threads.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function threads()
    {
        return $this->hasMany(Thread::class);
    }

    /**
     * An thread is owned by one forum.
     */
    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }

    public function getRouteKeyName()
    {
        return 'id';
    }
}
