<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;

class ResponseType extends Eloquent
{
    use HasFactory;

    /**
     * Seeded response type ids (see ResponseTypesTableSeeder).
     *
     * Only ATTENDING is used in practice — every attend path writes it.
     */
    public const ATTENDING = 1;

    public const INTERESTED = 2;

    public const INTERESTED_UNABLE = 3;

    public const UNINTERESTED = 4;

    public const CONFIRMED = 5;

    public const IGNORE = 6;

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
