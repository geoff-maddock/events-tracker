<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per entity per day of owner-dashboard counters.
 *
 * @property int $id
 * @property int $entity_id
 * @property \Illuminate\Support\Carbon $date
 * @property int $views
 * @property int $follows
 * @property int $clicks
 * @property int $responses
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Entity $entity
 */
class EntityStatDaily extends Model
{
    protected $table = 'entity_stats_daily';

    protected $fillable = ['entity_id', 'date', 'views', 'follows', 'clicks', 'responses'];

    protected $casts = [
        'date' => 'date',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
