<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Location extends Eloquent
{
    /**
     * @var Array
     *
     **/
    protected $fillable = [
        'name', 'slug', 'attn', 'address_one', 'address_two', 'neighborhood', 'city', 'state', 'postcode', 'country', 'latitude', 'longitude', 'location_type_id', 'visibility_id', 'entity_id', 'capacity', 'map_url'
    ];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * Get the entity related to the location
     *
     */
    public function entity(): HasOne
    {
        return $this->hasOne(Entity::class, 'id', 'entity_id');
    }

    /**
     * A location has one type
     *
     */
    public function locationType()
    {
        return $this->hasOne(LocationType::class, 'id', 'location_type_id');
    }

    /**
     * A location has one visibility
     *
     */
    public function visibility()
    {
        return $this->hasOne(Visibility::class, 'id', 'visibility_id');
    }

    /**
     * Returns visible events
     *
     */
    public function scopeVisible($query, $user)
    {
        $public = Visibility::where('name', '=', 'Public')->first();
        $guarded = Visibility::where('name', '=', 'Guarded')->first();

        $query->where('visibility_id', '=', $public ? $public->id : null)->orWhere('created_by', '=', ($user ? $user->id : null));
    }
}
