<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Contains users and objects they like.
 *
 * @property int    $object_id
 * @property string $object_type
 * @property User   $user
 */
class Like extends Eloquent
{
    protected $fillable = [
        'object_id', 'user_id', 'object_type',
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * The user who likes the object.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the object being liked.
     */
    public function getObject(): mixed
    {
        // how can i derive this class from a string?
        if (!$object = call_user_func('App\\Models\\'.ucfirst($this->object_type).'::find', $this->object_id)) { // Tag::find($id))
            return $object;
        }

        return null;
    }

    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }
}
