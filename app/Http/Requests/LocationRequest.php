<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Location;

class LocationRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Location::where([
            'created_by' => $this->user()->id
        ])->exists();
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
        ];
    }
}
