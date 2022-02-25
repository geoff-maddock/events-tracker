<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * App\Models\EntityStatus.
 *
 * @property int                                                           $id
 * @property string                                                        $name
 * @property \Illuminate\Support\Carbon|null                               $created_at
 * @property \Illuminate\Support\Carbon|null                               $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|\App\Models\Entity[] $entities
 * @property int|null                                                      $entities_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EntityStatus whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EntityStatus extends Eloquent
{
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
     * An event status can have many events.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function entities()
    {
        return $this->hasMany('App\Models\Entity');
    }

    /**
     * Returns the display class related to the status.
     */
    public function getDisplayClass(): string
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
