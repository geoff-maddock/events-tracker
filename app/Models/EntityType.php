<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @property string $name
 */
class EntityType extends Eloquent
{
    const SPACE = 1;

    const GROUP = 2;

    const INDIVIDUAL = 3;

    const INTEREST = 4;

    protected $fillable = [
        'name', 'slug', 'short',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
