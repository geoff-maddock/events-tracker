<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\MinimalSlugResource;
use App\Http\Resources\ProfileResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at,
            'last_active' => $this->lastActivity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'profile' => new ProfileResource($this->whenLoaded('profile', $this->profile)),
            'followed_tags' => MinimalSlugResource::collection($this->getTagsFollowing()),
            'followed_entities' => MinimalSlugResource::collection($this->getEntitiesFollowing()),
            'followed_series' => MinimalSlugResource::collection($this->getSeriesFollowing()),
            'followed_threads' => MinimalSlugResource::collection($this->getThreadsFollowing()),
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
