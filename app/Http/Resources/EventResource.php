<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Event;

// Resource for minimal relationship data
use App\Http\Resources\MinimalUserResource;
use App\Http\Resources\MinimalResource;

/**
 * @mixin \App\Models\Event
 */

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short' => $this->short,
            'visibility' => new MinimalResource($this->visibility),
            'description' => $this->description,
            'event_status' => new MinimalResource($this->eventStatus),
            'event_type' => new MinimalResource($this->eventType),
            'is_benefit' => $this->is_benefit,
            'do_not_repost' => $this->do_not_repost,
            'promoter' => $this->promoter ? new PromoterResource($this->promoter) : null,
            'venue' => $this->venue ? new VenueResource($this->venue) : null,
            'attending' => $this->attending,
            'attendees' => MinimalUserResource::collection($this->attendees),
            'like' => $this->like,
            'presale_price' => $this->presale_price,
            'door_price' => $this->door_price,
            'soundcheck_at' => $this->soundcheck_at,
            'door_at' => $this->door_at,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'min_age' => $this->min_age,
            'age_format' => $this->age_format,
            'series' => $this->series ? new MinimalSlugResource($this->series) : null,
            'primary_link' => $this->primary_link,
            'ticket_link' => $this->ticket_link,
            'tags' => MinimalSlugResource::collection($this->whenLoaded('tags', $this->tags)),
            'entities' => MinimalSlugResource::collection($this->whenLoaded('entities', $this->entities)),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'primary_photo' => $this->getPrimaryPhotoPath(),
            'primary_photo_thumbnail' => $this->getPrimaryPhotoThumbnailPath(),
            'photos' => $this->photos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'path' => $photo->getPath(),
                    'thumbnail_path' => $photo->getThumbnailPath(),
                ];
            })->toArray(),
        ];

        if (isset($this->popularity_score)) {
            $data['popularity_score'] = $this->popularity_score;
        }

        return $data;
    }
}
