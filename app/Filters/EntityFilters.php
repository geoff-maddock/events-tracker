<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class EntityFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('entities.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
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
}
