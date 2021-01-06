<?php

namespace App\Models;

use Image;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 */
class EventResponse extends Eloquent
{
    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d\\TH:i';

    /**
     * @var Array
     *
     **/
    protected $fillable = [
        'event_id', 'user_id', 'response_type_id'
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get the event that the response belongs to
     *
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Get the user that the response belongs to
     *
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the response type that the response belongs to
     *
     */
    public function responseType()
    {
        return $this->belongsTo('App\Models\ResponseType');
        ;
    }
}
