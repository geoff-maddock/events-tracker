<?php

namespace App\Http\Requests;

use App\Models\Location;

class LocationPatchRequest extends Request
{
    public function authorize(): bool
    {
        return Location::where(['created_by' => $this->user()->id])->exists();
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
