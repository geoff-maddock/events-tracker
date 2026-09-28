<?php

namespace App\Http\Requests;


class LocationPatchRequest extends Request
{
    public function authorize(): bool
    {
        // per-record checks (entity ownership) happen in the controller
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'min:3'],
            'slug' => ['sometimes', 'required', 'min:3', 'regex:/^[a-z0-9-]+$/'],
            'city' => ['sometimes', 'required', 'min:3'],
            'visibility_id' => ['sometimes', 'required'],
            'location_type_id' => ['sometimes', 'required'],
            'attn' => 'sometimes|nullable|string|max:255',
            'address_one' => 'sometimes|nullable|string|max:255',
            'address_two' => 'sometimes|nullable|string|max:255',
            'neighborhood' => 'sometimes|nullable|string|max:255',
            'state' => 'sometimes|nullable|string|max:255',
            'postcode' => 'sometimes|nullable|string|max:255',
            'country' => 'sometimes|nullable|string|max:255',
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'capacity' => 'sometimes|nullable|integer|min:0',
            'map_url' => 'sometimes|nullable|string|max:255',
            'entity_id' => 'sometimes|required|integer|exists:entities,id',
        ];
    }
}
