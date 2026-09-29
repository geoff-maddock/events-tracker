<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use App\Thread;

class ThreadRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|min:3',
            'body' => 'required|min:3',
            'visibility_id' => 'required',
            'forum_id' => 'required|exists:forums,id',
            // the rest of the form's fields, so controllers can save validated() alone (#2180);
            // locked_at/locked_by and views are server-controlled and deliberately have no rule
            'description' => 'nullable|string',
            'slug' => 'nullable|string|max:255',
            'event_id' => 'nullable|integer|exists:events,id',
            'thread_category_id' => 'nullable|integer|exists:thread_categories,id',
            'entity_list' => 'nullable|array',
            'entity_list.*' => 'integer|exists:entities,id',
            'series_list' => 'nullable|array',
            'series_list.*' => 'integer|exists:series,id',
        ];
    }
}
