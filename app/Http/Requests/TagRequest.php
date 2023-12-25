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
            'name' => 'required|min:3|max:255',
            'slug' => Rule::unique('tags')->ignore(isset($this->tag) ? $this->tag->id : ''),
        ];
    }
}
