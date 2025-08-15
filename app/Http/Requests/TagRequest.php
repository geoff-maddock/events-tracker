<?php

namespace App\Http\Requests;
use Illuminate\Validation\Rule;

class TagRequest extends Request
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
            'name' => 'required|min:3|max:16',
            'slug' => [
                'nullable',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('tags')->ignore(isset($this->tag) ? $this->tag->id : ''),
            ],
            'tag_type_id' => 'nullable|exists:tag_types,id',
            'description' => 'nullable|string',
        ];
    }
}
