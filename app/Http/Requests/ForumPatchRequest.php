<?php

namespace App\Http\Requests;

class ForumPatchRequest extends Request
{
    public function authorize(): bool
    {
        // forums are site structure: only admins may create or change one. Checked here so a
        // non-admin gets 403 before validation instead of a 422 for a bad payload.
        return $this->user() !== null && $this->user()->isAdmin();
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
