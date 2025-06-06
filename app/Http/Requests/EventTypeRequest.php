<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class EventTypeRequest extends Request
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
        $eventTypeId = $this->route('event_type')?->id ?? null;

        return [
            'name' => 'required|min:3|max:255',
            'slug' => [
                'required',
                'string',
                Rule::unique('event_types', 'slug')->ignore($eventTypeId),
            ],
        ];
    }
}
