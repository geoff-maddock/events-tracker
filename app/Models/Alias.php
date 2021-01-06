<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @property string $name
 */
class Alias extends Eloquent
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
        'name',
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get the entities that belong to the alias
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entities()
    {
        return $this->belongsToMany('App\Models\Entity')->withTimestamps();
    }
}
