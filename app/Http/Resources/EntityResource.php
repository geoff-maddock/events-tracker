<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Entity;
use App\Http\Resources\MinimalResource;

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
            'entity_status' => $this->entityStatus ? new MinimalResource($this->entityStatus) : null,
            'entity_type' => $this->entityType ? new MinimalResource($this->entityType) : null,
            'facebook_username' => $this->facebook_username,
            'twitter_username' => $this->twitter_username,
            'instagram_username' => $this->instagram_username,
            'started_at' => $this->started_at,
            'links' => $this->links->map(function ($link) {
                return [
                    'id' => $link->id,
                    'text' => $link->text,
                    'url' => $link->url,
                    'title' => $link->title,
                    'is_primary' => $link->is_primary,
                ];
            })->toArray(),
            'tags' => MinimalSlugResource::collection($this->tags)->resolve(),
            'roles' => MinimalResource::collection($this->roles)->resolve(),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'primary_photo' => $this->getPrimaryPhotoPath(),
            'primary_photo_thumbnail' => $this->getPrimaryPhotoThumbnailPath(),
            'primary_location' => $this->getPrimaryLocation(),
            'photos' => $this->photos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'path' => $photo->getPath(),
                    'thumbnail_path' => $photo->getThumbnailPath(),
                ];
            })->toArray(),
        ];
    }
}
