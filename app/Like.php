<?php

namespace App;

use Image;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Contains users and objects they like
 * @property int $object_id
 * @property string $object_type
 */
class Like extends Eloquent
{
    /**
     * The storage format of the model's date columns.
     */
    protected string $dateFormat = 'Y-m-d\\TH:i';

    /**
     * @var Array
     *
     **/
    protected $fillable = [
        'object_id', 'user_id', 'object_type'
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get the user that the response belongs to
     *
     */
    public function users()
    {
        return $this->belongsToMany('App\User')->withTimestamps();
    }

    /**
     * Get the object being liked
     *
     */
    public function getObject()
    {
        // how can i derive this class from a string?
        if (!$object = call_user_func('App\\' . ucfirst($this->object_type) . '::find', $this->object_id)) { // Tag::find($id))
            return $object;
        };
    }

    public function likeable()
    {
        return $this->morphTo();
    }
}
