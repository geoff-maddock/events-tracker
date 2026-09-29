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
            'description' => 'sometimes|nullable|string',
            'slug' => 'sometimes|nullable|string|max:255',
            'event_id' => 'sometimes|nullable|integer|exists:events,id',
            'thread_category_id' => 'sometimes|nullable|integer|exists:thread_categories,id',
            'entity_list' => 'sometimes|nullable|array',
            'entity_list.*' => 'integer|exists:entities,id',
            'series_list' => 'sometimes|nullable|array',
            'series_list.*' => 'integer|exists:series,id',
        ];
    }
}
