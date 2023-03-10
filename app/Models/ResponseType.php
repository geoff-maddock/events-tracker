<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;

class ResponseType extends Eloquent
{
    use HasFactory;

    protected $fillable = [
        'name', 'description',
    ];

    /**
     * A response type can have many event responses.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eventResponses()
    {
        return $this->belongsTo(EventResponse::class);
    }
}
