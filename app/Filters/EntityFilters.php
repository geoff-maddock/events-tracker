<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class EntityFilters extends QueryFilter
{
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
                $q->where('name', '=', ucfirst($value));
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
}
