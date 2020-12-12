<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'label', 'description', 'level'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function givePermissionTo(Permission $permission)
    {
        return $this->permissions()->save($permission);
    }

    public function assignPermission($permission)
    {
        return $this->permissions()->save(
            Permission::whereName($permission)->firstOrFail()
        );
    }

    /**
     * Get a list of user ids associated with the group
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getUserListAttribute()
    {
        return $this->users->pluck('id')->all();
    }

    /**
     * Get a list of permission ids associated with the group
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getPermissionListAttribute()
    {
        return $this->permissions->pluck('id')->all();
    }

    /**
     * Return a collection of groups with the passed user
     *
     * @param $slug
     *
     * @return Collection $groups
     */
    public static function getByUser(string $name)
    {
        // get a list of groups that have the passed user
        $groups = self::whereHas('user', function ($q) use ($name) {
            $q->where('name', '=', $name);
        });

        return $groups;
    }

    /**
     * Return a collection of groups with the passed permission
     *
     * @param $slug
     *
     * @return Collection $groups
     */
    public static function getByPermission(string $name)
    {
        // get a list of groups that have the passed permission
        $groups = self::whereHas('permission', function ($q) use ($name) {
            $q->where('name', '=', $name);
        });

        return $groups;
    }
}
