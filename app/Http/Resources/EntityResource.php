<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Entity;

/**
 * @mixin \App\Models\Entity
 */
class EntityResource extends JsonResource
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
        'short' => $this->short,
        'description' => $this->description,
        'entity_status' => $this->entityStatus,
        'entity_type' => $this->entityType,
        'facebook_username' => $this->facebook_username,
        'twitter_username' => $this->twitter_username,
        'started_at' => $this->started_at,
        'links' => $this->links,
        'tags' => $this->tags,
        'created_by' => $this->created_by,
        'updated_by' => $this->updated_by,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at
        ];
    }
}
