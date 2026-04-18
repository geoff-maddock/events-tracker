<?php

namespace App\Http\Requests;

class ForumPatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'min:3'],
            'slug' => ['sometimes', 'required', 'min:3', 'regex:/^[a-z0-9-]+$/'],
            'visibility_id' => ['sometimes', 'required'],
        ];
    }
}
