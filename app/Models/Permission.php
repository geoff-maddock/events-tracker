<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'label', 'description', 'level'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * Get a list of group ids associated with the permission
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getGroupListAttribute()
    {
        return $this->groups->pluck('id')->all();
    }

    /**
     * Return a collection of permissions related to the group.
     *
     * @return Collection $permissions
     *
     **/
    public static function getByGroup($group)
    {
        // get a list of blogs that have the passed tag
        $permissions = self::whereHas('group', function ($q) use ($group) {
            $q->where('name', '=', ucfirst($group));
        });

        return $permissions;
    }
}
