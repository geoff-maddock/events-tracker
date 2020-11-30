<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

class OccurrenceType extends Eloquent
{
    /**
     * @var Array
     *
     **/
    protected $fillable = [
        'name'
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
        return $this->hasMany('App\Series');
    }
}
