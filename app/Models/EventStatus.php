<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventStatus extends Eloquent
{
    /**
     * Seeded event status ids (see EventStatusesTableSeeder).
     */
    public const DRAFT = 1;

    public const PROPOSAL = 2;

    public const APPROVED = 3;

    public const HAPPENING_NOW = 4;

    public const PAST = 5;

    public const REJECTED = 6;

    public const CANCELLED = 7;

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
