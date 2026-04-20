<?php

namespace App\Http\Requests;

use App\Models\Blog;
use Illuminate\Validation\Rule;

class BlogPatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * PATCH semantics: every rule is `sometimes`, so only fields present in
     * the request body are validated. Constraints themselves match BlogRequest.
     */
    public function rules(): array
    {
        $routeBlog = $this->route('blog');
        $blogId = $routeBlog instanceof Blog ? $routeBlog->id : null;

        return [
            'name' => ['sometimes', 'required', 'min:3', 'max:255'],
            'slug' => [
                'sometimes', 'required', 'min:3', 'max:255', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('blogs', 'slug')->ignore($blogId),
            ],
            'body' => ['sometimes', 'required', 'min:3', 'max:65535'],
            'visibility_id' => ['sometimes', 'required', 'integer'],
            'content_type_id' => ['sometimes', 'required', 'integer'],
        ];
    }
}
