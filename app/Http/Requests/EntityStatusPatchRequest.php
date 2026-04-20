<?php

namespace App\Http\Requests;

class EntityStatusPatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'min:3', 'max:255', 'unique:entity_statuses,name'],
        ];
    }
}
