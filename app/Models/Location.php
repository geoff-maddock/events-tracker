<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\Location.
 *
 * @property string $map_url
 */
class Location extends Eloquent
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'attn', 'address_one', 'address_two', 'neighborhood', 'city', 'state', 'postcode', 'country', 'latitude', 'longitude', 'location_type_id', 'visibility_id', 'entity_id', 'capacity', 'map_url',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the entity related to the location.
     */
    public function entity(): HasOne
    {
        return $this->hasOne(Entity::class, 'id', 'entity_id');
    }

    /**
     * A location has one type.
     */
    public function locationType(): HasOne
    {
        return $this->hasOne(LocationType::class, 'id', 'location_type_id');
    }

    /**
     * A location has one visibility.
     */
    public function visibility(): HasOne
    {
        return $this->hasOne(Visibility::class, 'id', 'visibility_id');
    }

    /**
     * Returns visible locations.
     */
    public function scopeVisible(Builder $query, ?User $user): Builder
    {
        return $query->whereRelation('visibility','name','Public')->orWhere('created_by', '=', ($user ? $user->id : null));
    }
}
