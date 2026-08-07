<?php

namespace App\Filters;

use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filters for the admin feedback response list (issue #1998).
 *
 * Subject is polymorphic, so the event filter matches against the denormalized
 * subject columns rather than a relation — which is exactly why campaign and
 * subject live on survey_responses in the first place.
 */
class SurveyResponseFilters extends QueryFilter
{
    public function campaign(?string $value = null): Builder
    {
        if (! isset($value) || $value === '') {
            return $this->builder;
        }

        return $this->builder->whereHas('campaign', function ($q) use ($value) {
            $q->where('slug', $value);
        });
    }

    public function user(?string $value = null): Builder
    {
        if (! isset($value) || $value === '') {
            return $this->builder;
        }

        return $this->builder->whereHas('user', function ($q) use ($value) {
            $q->where('name', 'like', '%'.$value.'%');
        });
    }

    /**
     * Match by event name, or by exact event id when a number is supplied.
     */
    public function event(?string $value = null): Builder
    {
        if (! isset($value) || $value === '') {
            return $this->builder;
        }

        return $this->builder
            ->where('survey_responses.subject_type', 'event')
            ->whereIn('survey_responses.subject_id', function ($q) use ($value) {
                $q->select('id')->from('events')->where('name', 'like', '%'.$value.'%');

                if (is_numeric($value)) {
                    $q->orWhere('id', (int) $value);
                }
            });
    }

    /**
     * Responses whose overall rating is at least this value.
     */
    public function rating(?string $value = null): Builder
    {
        if (! isset($value) || $value === '') {
            return $this->builder;
        }

        return $this->builder->whereHas('answers', function ($q) use ($value) {
            $q->where('value_int', '>=', (int) $value)
                ->whereHas('question', function ($qq) {
                    $qq->where('type', SurveyQuestion::TYPE_RATING);
                });
        });
    }

    public function visibility(?string $value = null): Builder
    {
        if (! isset($value) || $value === '') {
            return $this->builder;
        }

        return $this->builder->where('survey_responses.visibility_id', (int) $value);
    }

    public function submitted_after(?string $value = null): Builder
    {
        if (! isset($value) || $value === '') {
            return $this->builder;
        }

        return $this->builder->where('survey_responses.submitted_at', '>=', $value);
    }

    public function submitted_before(?string $value = null): Builder
    {
        if (! isset($value) || $value === '') {
            return $this->builder;
        }

        return $this->builder->where('survey_responses.submitted_at', '<=', $value);
    }
}
