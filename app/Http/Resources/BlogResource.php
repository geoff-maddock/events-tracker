<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Blog;

/**
 * @mixin \App\Models\Blog
 */
class BlogResource extends JsonResource
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
        'visibility_id' => $this->visibility_id,
        'content_type_id' => $this->content_type_id,
        'body' => $this->body,
        'menu_id' => $this->menu_id,
        'sort_order' => $this->sort_order,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at
        ];
    }
}