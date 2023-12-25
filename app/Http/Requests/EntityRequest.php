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
        return [
            'name' => 'required|min:3|max:255',
            'slug' => Rule::unique('entities')->ignore(isset($this->entity) ? $this->entity->id : ''),
            'short' => 'max:255',
            'entity_type_id' => 'required',
        ];
    }
}
