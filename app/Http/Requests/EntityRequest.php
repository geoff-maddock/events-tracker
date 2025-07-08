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
                Rule::unique('entities', 'slug')->ignore($entitySlug),
            ],
            'short' => 'required|max:255',
            'description' => 'required',
            'entity_type_id' => 'required',
            'entity_status_id' => 'required',
        ];
    }
}
