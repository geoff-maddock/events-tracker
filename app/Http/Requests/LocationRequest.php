<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class LocationRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // per-record checks (entity ownership) happen in the controller
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|min:3',
            'slug' => 'required|min:3|regex:/^[a-z0-9-]+$/',
            'city' => 'required|min:3',
            'visibility_id' => 'required',
            'location_type_id' => 'required',
            'attn' => 'nullable|string|max:255',
            'address_one' => 'nullable|string|max:255',
            'address_two' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity' => 'nullable|integer|min:0',
            'map_url' => 'nullable|string|max:255',
            // a location always belongs to an entity (NOT NULL): optional on update, never null
            'entity_id' => 'sometimes|required|integer|exists:entities,id',
        ];
    }
}
