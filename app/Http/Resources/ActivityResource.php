<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Activity
 */
class ActivityResource extends JsonResource
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
            'user_id' => $this->user_id,
            'object_table' => $this->object_table,
            'object_id' => $this->object_id,
            'object_name' => $this->object_name,
            'child_object_table' => $this->child_object_table,
            'child_object_name' => $this->child_object_name,
            'child_object_id' => $this->child_object_id,
            'action_id' => $this->action_id,
            'message' => $this->message,
            'changes' => $this->changes,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
