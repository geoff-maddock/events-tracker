<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How many recipients an event reached on a channel on a given day.
 *
 * @property int $id
 * @property int $event_id
 * @property \Illuminate\Support\Carbon $date
 * @property string $channel
 * @property int $count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Event $event
 */
class EventReachDaily extends Model
{
    public const CHANNEL_DIGEST = 'digest';

    protected $table = 'event_reach_daily';

    protected $fillable = ['event_id', 'date', 'channel', 'count'];

    protected $casts = [
        'date' => 'date',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
