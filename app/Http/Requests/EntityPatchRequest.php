<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class EntityPatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * PATCH semantics: every rule is `sometimes`, so only fields present in the
     * request body are validated. Constraints themselves match EntityRequest.
     */
    public function rules(): array
    {
        $entityId = $this->route('entity')?->id ?? null;

        return [
            'name' => ['sometimes', 'required', 'min:3', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('entities', 'slug')->ignore($entityId),
            ],
            'short' => ['sometimes', 'required', 'max:255'],
            'description' => ['sometimes', 'required'],
            'entity_type_id' => ['sometimes', 'required'],
            'entity_status_id' => ['sometimes', 'required'],
        ];
    }
}
