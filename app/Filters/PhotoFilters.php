<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class PhotoFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder

            ->where('events.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function is_primary(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('photos.is_primary', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function is_event(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('photos.is_event', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function tag(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder
            ->whereHas('events.tags', function ($q) use ($value) {
                $q->where('slug', '=', $value);
            });
        } else {
            return $this->builder;
        }
    }

    public function related(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder
            ->whereHas('events.entities', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function created_at(array | string | null $value = null): Builder
    {
        // if not an array, do not process

        if (isset($value)) {
            if (!is_array($value)) {
                return $this->builder;
            }

            if (isset($value['start']) && $start = $value['start']) {
                $this->builder->whereDate('created_at', '>=', $start);
            }

            if (isset($value['end']) && $end = $value['end']) {
                $this->builder->whereDate('created_at', '<=', $end);
            }

            return $this->builder;
        } else {
            return $this->builder;
        }
    }
}
