<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ClickTrack
 *
 * @property int $id
 * @property int|null $event_id
 * @property int|null $user_id
 * @property int|null $venue_id
 * @property int|null $promoter_id
 * @property string|null $tags
 * @property string|null $user_agent
 * @property string|null $referrer
 * @property string|null $ip_address
 * @property string|null $country_code
 * @property string|null $city
 * @property \Illuminate\Support\Carbon|null $clicked_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event|null $event
 * @property-read \App\Models\User|null $user
 */
class ClickTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'venue_id',
        'promoter_id',
        'tags',
        'user_agent',
        'referrer',
        'ip_address',
        'country_code',
        'city',
        'clicked_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
    ];

    /**
     * Get the event that owns this click track.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the venue that owns this click track.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'venue_id');
    }

    /**
     * Get the promoter that owns this click track.
     */
    public function promoter(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'promoter_id');
    }

    /**
     * Get the user that owns this click track.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
