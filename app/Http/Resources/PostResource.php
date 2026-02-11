<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Post
 */
class PostResource extends JsonResource
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
            'thread_id' => $this->thread_id,
            'thread_name' => $this->thread ? $this->thread->name : null,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'body' => $this->body,
            'allow_html' => $this->allow_html,
            'content_type_id' => $this->content_type_id,
            'visibility_id' => $this->visibility_id,
            'views' => $this->views,
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
            'creator' => $this->creator ? new MinimalUserResource($this->creator) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
