<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'label', 'description', 'level'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function givePermissionTo(Permission $permission): Model
    {
        return $this->permissions()->save($permission);
    }

    public function assignPermission(string $permission): Model
    {
        return $this->permissions()->save(
            Permission::whereName($permission)->firstOrFail()
        );
    }

    /**
     * Get a list of user ids associated with the group.
     */
    public function getUserListAttribute(): array
    {
        return $this->users->pluck('id')->all();
    }

    /**
     * Get a list of permission ids associated with the group.
     */
    public function getPermissionListAttribute(): array
    {
        return $this->permissions->pluck('id')->all();
    }

    /**
     * Return a collection of groups with the passed user.
     */
    public static function getByUser(string $name): Builder
    {
        // get a list of groups that have the passed user
        return self::whereHas('user', function ($q) use ($name) {
            $q->where('name', '=', $name);
        });
    }

    /**
     * Return a collection of groups with the passed permission.
     */
    public static function getByPermission(string $name): Builder
    {
        // get a list of groups that have the passed permission
        $groups = self::whereHas('permission', function ($q) use ($name) {
            $q->where('name', '=', $name);
        });

        return $groups;
    }
}
