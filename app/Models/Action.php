<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int         $id
 * @property int|null    $user_id
 * @property string      $name
 * @property string|null $object_table
 * @property string|null $child_object_table
 */
class Action extends Eloquent
{
    const CREATE = 1;
    const UPDATE = 2;
    const DELETE = 3;
    const LOGIN = 4;
    const LOGOUT = 5;
    const FOLLOW = 6;
    const UNFOLLOW = 7;
    const ATTENDING = 8;
    const UNATTENDING = 9;
    const ACTIVATE = 10;
    const SUSPEND = 11;
    const REMINDER = 12;
    const IMPERSONATE = 13;
    const FAILED_LOGIN = 14;
    const NOTIFICATION = 15;
    const INSTAGRAM_POST = 16;

    // Additional actions
    const PASSWORD_RESET_REQUEST = 17;
    const PASSWORD_RESET = 18;
    const EXPORT = 19;

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
