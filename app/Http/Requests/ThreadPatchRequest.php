<?php

namespace App\Http\Requests;

class ThreadPatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'min:3'],
            'body' => ['sometimes', 'required', 'min:3'],
            'visibility_id' => ['sometimes', 'required'],
            'forum_id' => ['sometimes', 'required', 'exists:forums,id'],
        ];
    }
}
