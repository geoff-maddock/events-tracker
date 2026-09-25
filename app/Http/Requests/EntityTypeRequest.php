<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class EntityTypeRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // site configuration is admin-only; checked here so non-admins get 403 before validation
        return $this->user() !== null && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $entityTypeId = $this->route('entity_type')?->id ?? null;

        return [
            'name' => 'required|min:3|max:255',
            'slug' => [
                'required',
                'string',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('entity_types', 'slug')->ignore($entityTypeId),
            ],
            'short' => 'required|min:3|max:255',
        ];
    }

}
