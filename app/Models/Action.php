<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $object_table
 * @property string|null $child_object_table
 */
class Action extends Eloquent
{
    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d\\TH:i';

    /**
     * @var Array
     *
     **/
    protected $fillable = [
        'name', 'object_table', 'child_object_table'
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get the activity that belongs to the action
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function activity()
    {
        return $this->belongsToMany('App\Models\Activity')->withTimestamps();
    }
}
