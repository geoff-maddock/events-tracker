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
        $entityId = $this->route('entity')->id ?? null;

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
            'started_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:1971-01-01', 'before_or_equal:2037-12-31'],
            'facebook_username' => ['sometimes', 'nullable', 'string', 'max:64'],
            'instagram_username' => ['sometimes', 'nullable', 'string', 'max:64'],
            'twitter_username' => ['sometimes', 'nullable', 'string', 'max:64'],
            'role_list' => ['sometimes', 'nullable', 'array'],
            'role_list.*' => ['integer', 'exists:roles,id'],
        ];
    }
}
