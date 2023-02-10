<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Link;

/**
 * @mixin \App\Models\Entity
 */
class LinkResource extends JsonResource
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
        'url' => $this->url,
        'text' => $this->text,
        'image' => $this->image,
        'api' => $this->api,
        'title' => $this->title,
        'confirm' => $this->confirm,
        'is_primary' => $this->is_primary,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at
        ];
    }
}