<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Access types available for a group.
 */
class AccessType extends Eloquent
{
    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d\\TH:i';

    protected $fillable = [
        'name', 'object_table', 'child_object_table',
    ];


    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the activity that belongs to the action.
     */
    public function activity(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Activity')->withTimestamps();
    }
}
