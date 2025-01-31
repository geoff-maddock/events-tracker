<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Series;

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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short' => $this->short,
            'visibility' => $this->visibility,
            'description' => $this->description,
            'event_status' => $this->eventStatus,
            'event_type' => $this->eventType,
            'occurrence_type' => $this->occurrenceType,
            'occurrence_week' => $this->occurrenceWeek,
            'occurrence_day' => $this->occurrenceDay,
            'is_benefit' => $this->is_benefit,
            'promoter' => $this->promoter,
            'venue' => $this->venue,
            'attending' => $this->attending,
            'like' => $this->like,
            'presale_price' => $this->presale_price,
            'door_price' => $this->door_price,
            'soundcheck_at' => $this->soundcheck_at,
            'door_at' => $this->door_at,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'min_age' => $this->min_age,
            'primary_link' => $this->primary_link,
            'ticket_link' => $this->ticket_link,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'primary_photo' => $this->getPrimaryPhotoPath(),
            'primary_photo_thumbnail' => $this->getPrimaryPhotoThumbnailPath(),
            'primary_location' => $this->getPrimaryLocation(),
        ];
    }
}
