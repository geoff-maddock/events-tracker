<?php

namespace App\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class EntityFilters extends QueryFilter
{

    public function id(?string $value = null): Builder
    {
        $values = is_array($value) ? $value : array_filter(explode(',', (string) $value));

        if (count($values) > 1) {
            return $this->builder->whereIn('entities.id', $values);
        }

        if (isset($value)) {
            return $this->builder->where('entities.id', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function name(?string $value = null): Builder
    {
        if (!isset($value)) {
            return $this->builder;
        }

        return $this->builder->where(function ($query) use ($value) {
            $query->where('entities.name', 'like', '%'.$value.'%')
                ->orWhereHas('aliases', function ($q) use ($value) {
                    $q->where('name', '=', $value);
                });
        });
    }

    public function description(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('entities.description', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function entity_status(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('entityStatus', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function entity_type(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('entityType', function ($q) use ($value) {
                $q->where('entity_types.slug', '=', $value);
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

    public function role(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('roles', function ($q) use ($value) {
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
                $this->builder->whereDate('entities.created_at', '>=', $start);
            }

            if (isset($value['end']) && $end = $value['end']) {
                $this->builder->whereDate('entities.created_at', '<=', $end);
            }

            return $this->builder;
        } else {
            return $this->builder;
        }
    }

    public function started_at(array | string | null $value = null): Builder
    {
        // if not an array, do not process

        if (isset($value)) {
            if (!is_array($value)) {
                return $this->builder;
            }

            if (isset($value['start']) && $start = $value['start']) {
                $this->builder->whereDate('entities.started_at', '>=', $start);
            }

            if (isset($value['end']) && $end = $value['end']) {
                $this->builder->whereDate('entities.started_at', '<=', $end);
            }

            return $this->builder;
        } else {
            return $this->builder;
        }
    }

    public function active_range(?string $value = null): Builder
    {
        if (!isset($value)) {
            return $this->builder;
        }

        // Parse the value to determine the time period
        // Expected formats: "1-month", "1-year", "2-years", "5-years"
        $fromDate = null;

        switch ($value) {
            case '1-month':
                $fromDate = Carbon::now()->subMonth();
                break;
            case '1-year':
                $fromDate = Carbon::now()->subYear();
                break;
            case '2-years':
                $fromDate = Carbon::now()->subYears(2);
                break;
            case '5-years':
                $fromDate = Carbon::now()->subYears(5);
                break;
            default:
                // If invalid value, return builder without filtering
                return $this->builder;
        }

        // Filter entities that have events with start_at >= fromDate
        return $this->builder->whereHas('events', function ($q) use ($fromDate) {
            $q->where('events.start_at', '>=', $fromDate);
        });
    }
}
