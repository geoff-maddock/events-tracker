<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class SeriesFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('series.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function venue(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('venue', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function promoter(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('promoter', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function tag(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('tags', function ($q) use ($value) {
                $q->where('slug', '=', $value);
            });
        } else {
            return $this->builder;
        }
    }

    public function related(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('entities', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function event_type(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('eventType', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function occurrence_type(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('occurrenceType', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function occurrence_week(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('occurrenceWeek', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function occurrence_day(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('occurrenceDay', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function start_at(mixed $value = null): Builder
    {
        // if not an array, do not process

        if (isset($value)) {
            if (!is_array($value)) {
                return $this->builder;
            }

            if (isset($value['start']) && $start = $value['start']) {
                $this->builder->whereDate('start_at', '>=', $start);
            }

            if (isset($value['end']) && $end = $value['end']) {
                $this->builder->whereDate('start_at', '<=', $end);
            }

            return $this->builder;
        } else {
            return $this->builder;
        }
    }


    public function end_at(mixed $value = null): Builder
    {
        // if not an array, do not process

        if (isset($value)) {
            if (!is_array($value)) {
                return $this->builder;
            }

            if (isset($value['start']) && $start = $value['start']) {
                $this->builder->whereDate('end_at', '>=', $start);
            }

            if (isset($value['end']) && $end = $value['end']) {
                $this->builder->whereDate('end_at', '<=', $end);
            }

            return $this->builder;
        } else {
            return $this->builder;
        }
    }
    public function ages(?string $order = 'desc'): Builder
    {
        return $this->builder->orderBy('ages_id', $order);
    }

    public function visibility(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('visibility', function ($q) use ($value) {
                $q->where('id', '=', $value);
            });
        } else {
            return $this->builder;
        }
    }
}
