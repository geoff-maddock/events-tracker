<?php

namespace App\Http\Requests;
use Illuminate\Validation\Rule;

class EntityRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $entitySlug = $this->route('entity')?->id ?? null;

        return [
            'name' => 'required|min:3|max:255',
            'slug' => [
                'required',
                'string',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('entities', 'slug')->ignore($entitySlug),
            ],
            'short' => 'required|max:255',
            'description' => 'required',
            'entity_type_id' => 'required',
            'entity_status_id' => 'required',
            // started_at is stored in a MySQL TIMESTAMP column (valid range 1970–2038),
            // so keep it inside safe bounds to avoid a 500 from out-of-range dates.
            'started_at' => 'nullable|date|after_or_equal:1971-01-01|before_or_equal:2037-12-31',
        ];
    }
}
