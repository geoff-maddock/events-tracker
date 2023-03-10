<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @property string $name
 */
class UserStatus extends Eloquent
{
    use HasFactory;

    const PENDING = 1;

    const ACTIVE = 2;

    const SUSPENDED = 3;

    const BANNED = 4;

    const DELETED = 5;

    protected $fillable = [
        'name',
    ];

    /**
     * A status can have many users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
