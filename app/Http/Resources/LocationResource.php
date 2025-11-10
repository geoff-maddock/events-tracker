<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Location
 */
class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
        'id' => $this->id,
        'name' => $this->name,
        'slug' => $this->slug,
        'attn' => $this->attn,
        'address_one' => $this->address_one,
        'address_two' => $this->address_two,
        'neighborhood' => $this->neighborhood,
        'city' => $this->city,
        'state' => $this->state,
        'postcode' => $this->postcode,
        'country' => $this->country,
        'latitude' => $this->latitude,
        'longitude' => $this->longitude,
        'location_type_id' => $this->location_type_id,
        'visibility_id' => $this->visibility_id,
        'entity_id' => $this->entity_id,
        'capacity' => $this->capacity,
        'map_url' => $this->map_url,
        'entity' => $this->entity ? new MinimalSlugResource($this->entity) : null,
        ];
    }
}