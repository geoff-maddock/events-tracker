<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Profile
 */
class ProfileResource extends JsonResource
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
            'user_id' => $this->user_id,
            'bio' => $this->bio,
            'alias' => $this->alias,
            'location' => $this->location,
            'visibility_id' => $this->visibility_id,
            'facebook_username' => $this->facebook_username,
            'twitter_username' => $this->twitter_username,
            'instagram_username' => $this->instagram_username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'default_theme' => $this->default_theme,
            'setting_weekly_update' => $this->setting_weekly_update,
            'setting_daily_update' => $this->setting_daily_update,
            'setting_instant_update' => $this->setting_instant_update,
            'setting_forum_update' => $this->setting_forum_update,
            'setting_public_profile' => $this->setting_public_profile,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'links' => $this->links->map(function ($link) {
                return [
                    'id' => $link->id,
                    'text' => $link->text,
                    'url' => $link->url,
                    'title' => $link->title,
                    'is_primary' => $link->is_primary,
                ];
            })->toArray(),
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
