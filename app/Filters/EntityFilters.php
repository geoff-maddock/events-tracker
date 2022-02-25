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
