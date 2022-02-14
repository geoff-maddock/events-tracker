<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Str;

/**
 * @property string $name
 */
class EventType extends Eloquent
{
    use HasFactory;

    /**
     * @var array
     *
     **/
    protected $fillable = [
        'name',
    ];

    /**
     * Additional fields to treat as Carbon instances.
     *
     * @var array
     */
    protected $dates = [];

    /**
     * An event type can have many events.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function events()
    {
        return $this->hasMany('App\Models\Event');
    }

    public function getSlugAttribute()
    {
        return Str::slug($this->name, '-');
    }

    public function backgroundColor()
    {
        switch ($this->name) {
            case 'Art Opening':
                // ORANGE
                return '#F3722C';
            case 'Benefit':
                // LT GREEN
                return '#90BE6D';
            case 'Pop-up':
                // GREEN BLUE
                return '#43AA8B';
            case 'Live Stream':
                // RED
                return '#F94144';
             default:
                // BLUE
                return '#0a57ad';
        }
    }
}
