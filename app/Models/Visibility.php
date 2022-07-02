<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * App\Models\Visibility.
 *
 * @property int    $id
 * @property string $name
 **/
class Visibility extends Eloquent
{
    use HasFactory;

    const VISIBILITY_PROPOSAL = 1;

    const VISIBILITY_PRIVATE = 2;

    const VISIBILITY_PUBLIC = 3;

    const VISIBILITY_GUARDED = 4;

    const VISIBILITY_CANCELLED = 5;

    protected $fillable = [
        'name',
    ];

    /**
     * Additional fields to treat as Carbon instances.
     *
     * @var array
     */
    protected $dates = [];

    /**
     * A visibility can have many events.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * A visibility can have many menus.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function menus()
    {
        return $this->hasMany(Visibility::class);
    }
}
