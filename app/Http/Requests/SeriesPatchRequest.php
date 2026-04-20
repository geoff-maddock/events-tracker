<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class SeriesPatchRequest extends Request
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * PATCH semantics: every rule is `sometimes`, so only fields present in
     * the request body are validated. Constraints themselves match SeriesRequest.
     *
     * Note: the conditional occurrence_week_id/occurrence_day_id "required when
     * occurrence_type_id is monthly/weekly/etc." rules from SeriesRequest are
     * intentionally NOT enforced here. PATCH callers may update only some
     * fields, and inferring the resulting full-resource state would require
     * loading the model and merging — out of scope for partial-update validation.
     */
    public function rules(): array
    {
        $seriesId = $this->route('series')?->id ?? null;

        return [
            'name' => ['sometimes', 'required', 'min:3', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('series', 'slug')->ignore($seriesId),
            ],
            'short' => ['sometimes', 'required', 'min:3', 'max:255'],
            'length' => ['sometimes', 'integer'],
            'event_type_id' => ['sometimes', 'required'],
            'visibility_id' => ['sometimes', 'required'],
            'presale_price' => ['sometimes', 'nullable', 'numeric', 'between:0,999.99'],
            'door_price' => ['sometimes', 'nullable', 'numeric', 'between:0,999.99'],
            'occurrence_type_id' => ['sometimes', 'required'],
            'primary_link' => ['sometimes', 'nullable', 'regex:/^http:\/\/|https:\/\/|^$/', 'max:255'],
            'ticket_link' => ['sometimes', 'nullable', 'regex:/^http:\/\/|https:\/\/|^$/', 'max:255'],
            'occurrence_week_id' => ['sometimes', 'nullable'],
            'occurrence_day_id' => ['sometimes', 'nullable'],
        ];
    }
}
