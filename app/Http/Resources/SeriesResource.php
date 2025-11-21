<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Series;
use App\Http\Resources\MinimalResource;

/**
 * @mixin \App\Models\Series
 */
class SeriesResource extends JsonResource
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
            'visibility' => $this->visibility ? new MinimalResource($this->visibility) : null,
            'description' => $this->description,
            'event_status' => $this->eventStatus ? new MinimalResource($this->eventStatus) : null,
            'event_type' => $this->eventType ? new MinimalResource($this->eventType) : null,
            'occurrence_type' => $this->occurrenceType ? new MinimalResource($this->occurrenceType) : null,
            'occurrence_week' => $this->occurrenceWeek ? new MinimalResource($this->occurrenceWeek) : null,
            'occurrence_day' => $this->occurrenceDay ? new MinimalResource($this->occurrenceDay) : null,
            'occurrence_repeat' => $this->occurrenceRepeat,
            'is_benefit' => $this->is_benefit,
            'promoter' => $this->promoter ? new MinimalSlugResource($this->promoter) : null,
            'venue' => $this->venue ? new MinimalSlugResource($this->venue) : null,
            'attending' => $this->attending,
            'like' => $this->like,
            'presale_price' => $this->presale_price,
            'door_price' => $this->door_price,
            'soundcheck_at' => $this->soundcheck_at,
            'door_at' => $this->door_at,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'min_age' => $this->min_age,
            'age_format' => $this->age_format,
            'primary_link' => $this->primary_link,
            'ticket_link' => $this->ticket_link,
            'tags' => MinimalSlugResource::collection($this->whenLoaded('tags', $this->tags)),
            'entities' => MinimalSlugResource::collection($this->whenLoaded('entities', $this->entities)),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'founded_at' => $this->founded_at,
            'cancelled_at' => $this->cancelled_at,
            'primary_photo' => $this->getPrimaryPhotoPath(),
            'primary_photo_thumbnail' => $this->getPrimaryPhotoThumbnailPath(),
            'next_event' => $this->nextEvent(),
            'next_start_at' => $this->nextPlannedStartAt(),
        ];

        if (isset($this->popularity_score)) {
            $data['popularity_score'] = $this->popularity_score;
        }

        return $data;
    }
}
