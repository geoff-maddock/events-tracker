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
        ];
    }
}
