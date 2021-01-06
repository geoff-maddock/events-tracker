<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @property int $id
 * @property string $name
 */
class OccurrenceDay extends Eloquent
{
    /**
     * @var Array
     *
     **/
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
     * An occurence type can have many event templates
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function series()
    {
        return $this->hasMany(Series::class);
    }

    public function __toString()
    {
        return (string) $this->name;
    }
}
