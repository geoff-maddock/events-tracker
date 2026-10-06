<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class EventPatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepend a dash to digit-leading slugs before validation so the
     * unique rule checks the value that will actually be stored.
     */
    protected function prepareForValidation(): void
    {
        $slug = $this->input('slug');

        if (is_string($slug) && '' !== $slug && ctype_digit($slug[0])) {
            $this->merge(['slug' => '-'.$slug]);
        }
    }

    /**
     * PATCH semantics: every rule is `sometimes`, so only fields present in
     * the request body are validated. Constraints themselves match EventRequest.
     */
    public function rules(): array
    {
        $eventId = $this->route('event')->id ?? null;

        return [
            'name' => ['sometimes', 'required', 'min:3', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('events', 'slug')->ignore($eventId),
            ],
            'short' => ['sometimes', 'nullable', 'max:255'],
            'start_at' => ['sometimes', 'required', 'date'],
            'end_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_at'],
            'door_at' => ['sometimes', 'nullable', 'date', 'before_or_equal:start_at'],
            'event_type_id' => ['sometimes', 'required'],
            'visibility_id' => ['sometimes', 'required'],
            'presale_price' => ['sometimes', 'nullable', 'numeric', 'between:0,999.99'],
            'door_price' => ['sometimes', 'nullable', 'numeric', 'between:0,999.99'],
            'primary_link' => ['sometimes', 'nullable', 'url:http,https', 'max:255'],
            'ticket_link' => ['sometimes', 'nullable', 'url:http,https', 'max:255'],
            // Only validated when present. entity_list must be existing entity
            // IDs so a bad body returns 422 rather than a raw SQL error on the
            // entity_id integer column (EVENTREPO-XV).
            'entity_list' => ['sometimes', 'nullable', 'array'],
            'entity_list.*' => ['integer', 'exists:entities,id'],
            'created_by' => 'sometimes|nullable|integer|exists:users,id',
            'description' => 'sometimes|nullable|string',
            'venue_id' => 'sometimes|nullable|integer|exists:entities,id',
            'promoter_id' => 'sometimes|nullable|integer|exists:entities,id',
            'series_id' => 'sometimes|nullable|integer|exists:series,id',
            'event_status_id' => 'sometimes|nullable|integer|exists:event_statuses,id',
            'min_age' => 'sometimes|nullable|integer|min:0|max:99',
            'is_benefit' => 'sometimes|nullable|boolean',
            'do_not_repost' => 'sometimes|nullable|boolean',
            'soundcheck_at' => 'sometimes|nullable|date',
            'cancelled_at' => 'sometimes|nullable|date',
        ];
    }
}
