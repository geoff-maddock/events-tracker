<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Contains users and objects they like
 * @property int $object_id
 * @property string $object_type
 * @property User $user
 */
class Like extends Eloquent
{
    /**
     * @var Array
     *
     **/
    protected $fillable = [
        'object_id', 'user_id', 'object_type'
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * The user who likes the object
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the object being liked
     *
     */
    public function getObject()
    {
        // how can i derive this class from a string?
        if (!$object = call_user_func('App\\Models\\' . ucfirst($this->object_type) . '::find', $this->object_id)) { // Tag::find($id))
            return $object;
        };
    }

    public function likeable()
    {
        return $this->morphTo();
    }
}
