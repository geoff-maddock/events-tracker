<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Storage;

/**
 * @mixin \App\Models\Photo
 */
class PhotoResource extends JsonResource
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
        'photo' => Storage::disk('external')->url($this->getStoragePath()),
        'photo_thumbnail' => Storage::disk('external')->url($this->getStorageThumbnail()),
        ];
    }
}
