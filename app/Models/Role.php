<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Role extends Eloquent
{
    use HasFactory;

    /**
     * @var Array
     *
     **/
    protected $fillable = [
        'name', 'slug', 'short'
    ];

    protected $appends = ['plural'];

    protected $dates = ['updated_at'];

    /**
     * Get the entities that belong to the role
     *
     * @ return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function entities()
    {
        return $this->belongsToMany(Entity::class)->withTimestamps();
    }

    /**
     * Get the plural version of the role
     *
     */
    public function getPluralAttribute()
    {
        return ucfirst(strtolower($this->name . 's'));
    }

    /**
     * Convert all role names to ucfirst
     *
     */
    public function getNameAttribute($value)
    {
        return ucfirst(strtolower($value));
    }
}
