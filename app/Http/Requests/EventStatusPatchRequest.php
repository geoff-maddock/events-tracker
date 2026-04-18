<?php

namespace App\Http\Requests;

class EventStatusPatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'min:3', 'max:255', 'unique:event_statuses,name'],
        ];
    }
}
