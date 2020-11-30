<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

class EntityStatus extends Eloquent
{
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

    /**
     * An event status can have many events
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function entities()
    {
        return $this->hasMany('App\Entity');
    }

    /**
     * Returns the display class related to the status
     *
     */
    public function getDisplayClass()
    {
        $class = '';
        switch ($this->name) {
            case 'Draft':
                $class = 'warning';
                break;
            case 'Inactive':
                $class = 'muted';
                break;
            default:
                $class = 'primary';
        }

        return $class;
    }
}
