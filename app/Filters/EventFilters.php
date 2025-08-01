<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class EventFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('events.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function venue(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('venue', function ($q) use ($value) {
                $q->where('name','like', '%'.$value.'%');
            });
        } else {
            return $this->builder;
        }
    }

    public function promoter(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('promoter', function ($q) use ($value) {
                $q->where('name','like', '%'.$value.'%');
            });
        } else {
            return $this->builder;
        }
    }

    public function tag(mixed $value = null): Builder
    {
        if (!isset($value)) {
            return $this->builder;
        }

        $values = is_array($value) ? $value : array_filter(explode(',', (string) $value));

        if (count($values) > 1) {
            return $this->builder->whereHas('tags', function ($q) use ($values) {
                $q->whereIn('slug', $values);
            });
        }

        return $this->builder->whereHas('tags', function ($q) use ($values) {
            $q->where('slug', '=', $values[0]);
        });
    }

    public function tag_all(mixed $value = null): Builder
    {
        if (!isset($value)) {
            return $this->builder;
        }

        $values = is_array($value) ? $value : array_filter(explode(',', (string) $value));

        foreach ($values as $val) {
            $this->builder->whereHas('tags', function ($q) use ($val) {
                $q->where('slug', '=', $val);
            });
        }

        return $this->builder;
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

    public function series(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('series', function ($q) use ($value) {
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
                $q->where('slug', '=', $value);
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

                $this->builder->where('start_at', '>=', $start);
            }

            if (isset($value['end']) && $end = $value['end']) {
                $this->builder->where('start_at', '<=', $end);
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
                $this->builder->where('end_at', '>=', $start);
            }

            if (isset($value['end']) && $end = $value['end']) {
                $this->builder->where('end_at', '<=', $end);
            }

            return $this->builder;
        } else {
            return $this->builder;
        }
    }

    public function ages(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('events.min_age', '=', $value);
        } else {
            return $this->builder;
        }
    }
}
