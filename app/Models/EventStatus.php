<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventStatus extends Eloquent
{
    protected $fillable = [
        'name',
    ];


    /**
     * An event status can have many events.
     */
    public function events(): HasMany
    {
        return $this->hasMany('App\Models\Event');
    }
}
