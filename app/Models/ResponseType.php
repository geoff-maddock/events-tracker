<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;

class ResponseType extends Eloquent
{
    use HasFactory;

    /**
     * @var Array
     *
     **/
    protected $fillable = [
        'name', 'description'
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
        return $this->belongsTo(EventResponse::class);
    }
}
