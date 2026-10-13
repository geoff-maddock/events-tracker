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
        // bio and social handles stay private until the account is trusted (#2312)
        $viewer = $request->user() ?? $request->user('sanctum');
        $shown = $this->user->showsProfileContentTo($viewer);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'bio' => $shown ? $this->bio : null,
            'alias' => $this->alias,
            'location' => $this->location,
            'visibility_id' => $this->visibility_id,
            'facebook_username' => $shown ? $this->facebook_username : null,
            'twitter_username' => $shown ? $this->twitter_username : null,
            'instagram_username' => $shown ? $this->instagram_username : null,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'default_theme' => $this->default_theme,
            'setting_weekly_update' => $this->setting_weekly_update,
            'setting_daily_update' => $this->setting_daily_update,
            'setting_instant_update' => $this->setting_instant_update,
            'setting_forum_update' => $this->setting_forum_update,
            'setting_notify_threads_by_follow' => $this->setting_notify_threads_by_follow,
            'setting_public_profile' => $this->setting_public_profile,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
