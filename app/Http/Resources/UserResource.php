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
    /**
     * Force the private fields on, for a caller that isn't signed in yet but owns the record (registration).
     */
    private bool $includePrivate = false;

    public function includePrivate(): static
    {
        $this->includePrivate = true;

        return $this;
    }

    public function toArray($request)
    {
        // email and follow lists are only for the user themselves or grant_access (#2164)
        $viewer = $request->user();
        $private = $this->includePrivate
            || ($viewer !== null && ($viewer->id === $this->id || $viewer->can('grant_access')));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($private, fn () => $this->email),
            'status' => $this->status,
            'email_verified_at' => $this->when($private, fn () => $this->email_verified_at),
            'last_active' => $this->lastActivity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'profile' => new ProfileResource($this->whenLoaded('profile', $this->profile)),
            'followed_tags' => $this->when($private, fn () => MinimalSlugResource::collection($this->relationLoaded('followedTags') ? $this->followedTags : $this->getTagsFollowing())),
            'followed_entities' => $this->when($private, fn () => MinimalSlugResource::collection($this->relationLoaded('followedEntities') ? $this->followedEntities : $this->getEntitiesFollowing())),
            'followed_series' => $this->when($private, fn () => MinimalSlugResource::collection($this->relationLoaded('followedSeries') ? $this->followedSeries : $this->getSeriesFollowing())),
            'followed_threads' => $this->when($private, fn () => MinimalSlugResource::collection($this->relationLoaded('followedThreads') ? $this->followedThreads : $this->getThreadsFollowing())),
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
