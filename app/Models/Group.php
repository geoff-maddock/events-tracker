<?php

namespace App\Models;

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
}
