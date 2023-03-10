<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @property string $name
 */
class ContentType extends Eloquent
{
    const PLAIN_TEXT = 1;

    const HTML = 2;

    protected $fillable = [
        'name',
    ];


    public function __toString()
    {
        return (string) $this->name;
    }
}
