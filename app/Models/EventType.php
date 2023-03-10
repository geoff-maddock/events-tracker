<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Str;

/**
 * @property string $name
 */
class EventType extends Eloquent
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * An event type can have many events.
     */
    public function events(): HasMany
    {
        return $this->hasMany('App\Models\Event');
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name, '-');
    }

    public function backgroundColor(): string
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
