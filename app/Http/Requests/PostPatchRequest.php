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
        // no thread_id: a post stays in its thread. The edit form sends it as a hidden
        // field, and accepting it let an author move a post into any thread (#2180)
        return [
            'body' => ['sometimes', 'required', 'min:3'],
            'visibility_id' => ['sometimes', 'required'],
            'name' => 'sometimes|nullable|string|max:255',
            'slug' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
        ];
    }
}
