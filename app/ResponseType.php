<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

class ResponseType extends Eloquent
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
     * A response type can have many event responses
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eventResponses()
    {
        return $this->belongsTo('App\EventResponse');
    }
}
