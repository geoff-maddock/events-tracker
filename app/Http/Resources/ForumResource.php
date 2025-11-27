<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Forum;

/**
 * @mixin \App\Models\Forum
 */
class ForumResource extends JsonResource
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
            'description' => $this->description,
            'visibility' => $this->visibility ? new MinimalResource($this->visibility) : null,
            'threads_count' => $this->threads_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}