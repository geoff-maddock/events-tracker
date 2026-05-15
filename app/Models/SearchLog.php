<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\SearchLog
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $query
 * @property int $results_count
 * @property string $source
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \App\Models\User|null $user
 */
class SearchLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'query',
        'results_count',
        'source',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'results_count' => 'integer',
        'created_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
