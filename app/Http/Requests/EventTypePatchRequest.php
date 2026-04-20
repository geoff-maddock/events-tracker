<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class EventTypePatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $eventTypeId = $this->route('event_type')?->id ?? null;

        return [
            'name' => ['sometimes', 'required', 'min:3', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('event_types', 'slug')->ignore($eventTypeId),
            ],
        ];
    }
}
