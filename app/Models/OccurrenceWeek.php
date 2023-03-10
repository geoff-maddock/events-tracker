<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @property int    $id
 * @property string $name
 */
class OccurrenceWeek extends Eloquent
{
    protected $fillable = [
        'name',
    ];

    /**
     * An occurence type can have many event templates.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function series()
    {
        return $this->hasMany(Series::class);
    }
}
