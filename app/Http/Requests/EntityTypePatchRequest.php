<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class EntityTypePatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entityTypeId = $this->route('entity_type')?->id ?? null;

        return [
            'name' => ['sometimes', 'required', 'min:3', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('entity_types', 'slug')->ignore($entityTypeId),
            ],
            'short' => ['sometimes', 'required', 'min:3', 'max:255'],
        ];
    }
}
