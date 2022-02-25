<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Return a collection of permissions related to the group.
     *
     **/
    public static function getByGroup(string $group): Builder
    {
        // get a list of blogs that have the passed tag
        return self::whereHas('group', function ($q) use ($group) {
            $q->where('name', '=', ucfirst($group));
        });
    }
}
