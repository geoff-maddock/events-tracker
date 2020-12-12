<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class ContentType extends Eloquent
{
    const PLAIN_TEXT = 1;

    const HTML = 2;

    /**
     * @var Array
     *
     **/
    protected $fillable = [
        'name'
    ];

    /**
     * Additional fields to treat as Carbon instances.
     *
     * @var array
     */
    protected $dates = [];

    public function __toString()
    {
        return (string) $this->name;
    }
}
