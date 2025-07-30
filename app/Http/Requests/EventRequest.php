<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Http\Requests\Request;

class EventRequest extends Request
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
        $eventSlug = $this->route('event')?->id ?? null;

        return [
            'name' => 'required|min:3|max:255',
            'slug' => [
                'required',
                'string',
                Rule::unique('events', 'slug')->ignore($eventSlug),
            ],
            'short' => 'max:255',
            'start_at' => 'required|date',
            'event_type_id' => 'required',
            'visibility_id' => 'required',
            'presale_price' => 'nullable|numeric|between:0,999.99',
            'door_price' => 'nullable|numeric|between:0,999.99',
            'primary_link' => ['nullable','regex:/^http:\/\/|https:\/\/|^$/','max:255'],
            'ticket_link' => ['nullable','regex:/^http:\/\/|https:\/\/|^$/','max:255'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'An event name is required',
            'name.min' => 'An event name must be at least 3 characters',
            'name.max' => 'An event name must be less than 255 characters',
            'slug.required' => 'An event slug is required',
            'slug.min' => 'An event slug must be at least 3 characters',
            'slug.max' => 'An event slug must be less than 255 characters',
            'short.max' => 'An event short description must be less than 255 characters',
            'start_at.required' => 'An event start date is required',
            'start_at.date' => 'An event start date must be a valid date',
            'event_type_id.required' => 'An event type is required',
            'visibility_id.required' => 'A visibility is required',
            'primary_link.regex' => 'A primary link must be a valid URL starting with http:// or https:// or blank',
            'primary_link.max' => 'A primary link must be less than 255 characters',
            'ticket_link.regex' => 'A ticket link must be a valid URL starting with http:// or https:// or blank',
            'ticket_link.max' => 'A ticket link must be less than 255 characters',
        ];
    }
}
