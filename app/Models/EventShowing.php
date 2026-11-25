<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One additional date and time of a short-run event (#2375).
 *
 * The event's own start_at is its first showing and has no row here. Times are
 * naive wall-clock values, like the event's (see App\Services\EventTime).
 *
 * @property int $id
 * @property int $event_id
 * @property \Illuminate\Support\Carbon $start_at
 * @property \Illuminate\Support\Carbon|null $end_at
 * @property \Illuminate\Support\Carbon|null $door_at
 */
class EventShowing extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_at',
        'end_at',
        'door_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'door_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
