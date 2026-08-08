<?php

namespace App\Http\Requests;

use App\Models\DiscordTargetCriterion;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Validation for creating and editing a Discord repost target (issue #2058).
 *
 * Two rules here are about safety rather than data hygiene:
 *
 *  - On update, webhook_url is optional and a blank value keeps the stored
 *    one. The edit form must never render the existing secret back into HTML
 *    just so it can be resubmitted.
 *  - A target with no criteria and match_all unset is rejected. Without this,
 *    the two easiest mistakes are the two worst: an accidental firehose of
 *    every event on the site into someone's channel, or a channel that looks
 *    configured and silently receives nothing.
 */
class DiscordTargetRequest extends Request
{
    /**
     * Route middleware already restricts these endpoints to admins.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $target = $this->route('discordTarget');
        $targetId = is_object($target) ? $target->id : $target;

        return [
            'name' => 'required|string|min:3|max:96',
            'slug' => [
                'nullable', 'string', 'max:96', 'alpha_dash',
                Rule::unique('discord_targets', 'slug')->ignore($targetId),
            ],

            // Blank on update means "keep the stored webhook".
            'webhook_url' => [
                $targetId ? 'nullable' : 'required',
                'string',
                'url',
                'starts_with:https://discord.com/api/webhooks/,https://discordapp.com/api/webhooks/',
                'regex:#/webhooks/\d+/[\w-]+$#',
            ],

            'is_enabled' => 'boolean',
            'match_all' => 'boolean',
            'requires_photo' => 'boolean',
            'post_on_create' => 'boolean',
            'post_reminders' => 'boolean',
            'post_digest' => 'boolean',
            'allow_manual' => 'boolean',

            // The form supplies "7, 1" as a single text field; the API-shaped
            // array is accepted too.
            'reminder_offsets_csv' => 'nullable|string|max:64|regex:/^[\d\s,]*$/',
            'reminder_offsets' => 'nullable|array|max:6',
            'reminder_offsets.*' => 'integer|min:0|max:60',

            'digest_day' => 'integer|min:0|max:6',
            'digest_hour' => 'integer|min:0|max:23',
            'digest_window_days' => 'integer|min:1|max:60',

            'mention' => 'nullable|string|max:64',
            'username' => 'nullable|string|max:80',
            'avatar_url' => 'nullable|url|max:255',
            'embed_color' => 'nullable|integer|min:0|max:16777215',

            // Criteria arrive grouped by mode and type, which is what an HTML
            // multi-select produces: include[tag][] = [1, 2].
            'include' => 'nullable|array',
            'include.*' => 'nullable|array|max:200',
            'include.*.*' => 'integer|min:1',
            'exclude' => 'nullable|array',
            'exclude.*' => 'nullable|array|max:200',
            'exclude.*.*' => 'integer|min:1',
        ];
    }

    /**
     * Reminder offsets as a clean, descending list of days, or null to fall
     * back to the site default. Accepts either the form's CSV field or an
     * array.
     *
     * @return array<int, int>|null
     */
    public function reminderOffsets(): ?array
    {
        $offsets = $this->input('reminder_offsets');

        if (! is_array($offsets)) {
            $csv = trim((string) $this->input('reminder_offsets_csv', ''));

            if ('' === $csv) {
                return null;
            }

            $offsets = preg_split('/[\s,]+/', $csv, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        $offsets = array_filter(
            array_map('intval', $offsets),
            fn (int $days): bool => $days >= 0 && $days <= 60
        );

        $offsets = array_values(array_unique($offsets));
        rsort($offsets);

        return [] === $offsets ? null : $offsets;
    }

    /**
     * Flatten the grouped criteria input into rows the model layer stores.
     *
     * @return array<int, array{criteria_type: string, criteria_id: int, mode: string}>
     */
    public function criteriaRows(): array
    {
        $rows = [];

        foreach ([DiscordTargetCriterion::MODE_INCLUDE, DiscordTargetCriterion::MODE_EXCLUDE] as $mode) {
            $groups = $this->input($mode, []);

            if (! is_array($groups)) {
                continue;
            }

            foreach ($groups as $type => $ids) {
                // An unrecognised type is dropped rather than stored — a
                // criterion the matcher can't interpret matches nothing.
                if (! in_array($type, DiscordTargetCriterion::TYPES, true) || ! is_array($ids)) {
                    continue;
                }

                foreach ($ids as $id) {
                    $rows[] = [
                        'criteria_type' => (string) $type,
                        'criteria_id' => (int) $id,
                        'mode' => $mode,
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'webhook_url.starts_with' => 'That is not a Discord webhook URL. In Discord: Channel Settings → Integrations → Webhooks → Copy Webhook URL.',
            'webhook_url.regex' => 'That Discord webhook URL looks incomplete — it should end with the webhook id and token.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $includes = array_filter(
                $this->criteriaRows(),
                fn (array $row): bool => DiscordTargetCriterion::MODE_INCLUDE === $row['mode']
            );

            if ([] === $includes && ! $this->boolean('match_all')) {
                $validator->errors()->add(
                    'include',
                    'Add at least one include filter, or tick "receive all events" — '
                    .'a target with neither would silently receive nothing.'
                );
            }
        });
    }
}
