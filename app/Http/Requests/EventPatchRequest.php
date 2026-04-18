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
     * PATCH semantics: every rule is `sometimes`, so only fields present in
     * the request body are validated. Constraints themselves match EventRequest.
     */
    public function rules(): array
    {
        $eventId = $this->route('event')?->id ?? null;

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
            'primary_link' => ['sometimes', 'nullable', 'regex:/^http:\/\/|https:\/\/|^$/', 'max:255'],
            'ticket_link' => ['sometimes', 'nullable', 'regex:/^http:\/\/|https:\/\/|^$/', 'max:255'],
        ];
    }
}
