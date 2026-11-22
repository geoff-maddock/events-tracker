<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'label', 'description', 'level'];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * Get a list of group ids associated with the permission.
     */
    public function getGroupListAttribute(): array
    {
        return $this->groups->pluck('id')->all();
    }
}
