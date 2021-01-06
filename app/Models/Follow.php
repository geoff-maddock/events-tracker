<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * App\Models\Follow
 *
 * @property int $id
 * @property int $user_id
 * @property User $user
 * @property string|null $object_type
 * @property int|null $object_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Eloquent|\Eloquent $object
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder|Follow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Follow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Follow query()
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Follow whereUserId($value)
 * @mixin \Eloquent
 */
class Follow extends Eloquent
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
        'object_id',
        'user_id',
        'object_type'
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get the user who follows the object
     *
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the object being followed
     *
     */
    public function getObject()
    {
        // how can i derive this class from a string?
        if (!$object = call_user_func('App\\' . ucfirst($this->object_type) . '::find', $this->object_id)) {
            return $object;
        };
    }

    public function object()
    {
        return $this->morphTo();
    }
}
