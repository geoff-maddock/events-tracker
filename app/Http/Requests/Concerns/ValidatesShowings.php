<?php

namespace App\Http\Requests\Concerns;

use App\Models\Event;

/**
 * The `showings` input shared by the event requests: the additional dates of a
 * short-run event (#2375), each with a start_at and optional end_at / door_at.
 */
trait ValidatesShowings
{
    /**
     * Drop the rows the form's repeater left blank, so they neither fail
     * validation nor count towards the limit.
     */
    protected function pruneBlankShowings(): void
    {
        $showings = $this->input('showings');
        if (!is_array($showings)) {
            return;
        }

        $filled = array_filter($showings, function ($row): bool {
            if (!is_array($row)) {
                return true;
            }

            return array_filter($row, fn ($value) => null !== $value && '' !== $value) !== [];
        });

        $this->merge(['showings' => array_values($filled)]);
    }

    /**
     * Every showing starts after the event's own start_at, which stays the
     * first date: the one in this request, or the stored one on a PATCH that
     * doesn't send it.
     *
     * @return array<string, array<int, string>>
     */
    protected function showingRules(): array
    {
        $first = $this->input('start_at');
        if (!is_string($first) || false === strtotime($first)) {
            $event = $this->route('event');
            $first = $event instanceof Event ? $event->start_at?->format('Y-m-d H:i:s') : null;
        }

        $startRules = ['required', 'date', 'distinct'];
        if (null !== $first) {
            $startRules[] = 'after:'.$first;
        }

        return [
            'showings' => ['sometimes', 'nullable', 'array', 'max:'.Event::MAX_SHOWINGS],
            'showings.*' => ['array'],
            'showings.*.start_at' => $startRules,
            'showings.*.end_at' => ['nullable', 'date', 'after_or_equal:showings.*.start_at'],
            'showings.*.door_at' => ['nullable', 'date', 'before_or_equal:showings.*.start_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function showingMessages(): array
    {
        return [
            'showings.max' => 'An event can have at most '.Event::MAX_SHOWINGS.' additional showings',
            'showings.*.start_at.required' => 'Each additional showing needs a start date and time',
            'showings.*.start_at.date' => 'Each additional showing must start at a valid date',
            'showings.*.start_at.distinct' => 'Two additional showings have the same start time',
            'showings.*.start_at.after' => 'Additional showings must start after the event start time',
            'showings.*.end_at.after_or_equal' => 'A showing must end after it starts',
            'showings.*.door_at.before_or_equal' => 'A showing\'s doors must open before it starts',
        ];
    }
}
