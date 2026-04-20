<?php

namespace App\Http\Requests;

class PostPatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * PATCH semantics: every rule is `sometimes`, so only fields present in
     * the request body are validated. Constraints themselves match PostRequest.
     */
    public function rules(): array
    {
        return [
            'body' => ['sometimes', 'required', 'min:3'],
            'visibility_id' => ['sometimes', 'required'],
            'thread_id' => ['sometimes', 'required'],
        ];
    }
}
