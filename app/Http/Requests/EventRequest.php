<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Http\Requests\Request;

class EventRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|min:3|max:255',
            'slug' => Rule::unique('events')->ignore(isset($this->event) ? $this->event->id : ''),
            'short' => 'max:255',
            'start_at' => 'required|date',
            'event_type_id' => 'required',
            'visibility_id' => 'required',
            'presale_price' => 'nullable|numeric|between:0,999.99',
            'door_price' => 'nullable|numeric|between:0,999.99',
        ];
    }
}
