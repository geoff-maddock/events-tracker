<?php

namespace App\Filters;

class EntityFilters extends QueryFilter
{
    public function name($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('entities.name', 'like', '%' . $value . '%');
        } else {
            return $this->builder;
        }
    }

    public function entity_type($value = null)
    {
        if (isset($value)) {
            return $this->builder->whereHas('entityType', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
            ;
        } else {
            return $this->builder;
        }
    }

    public function tag($value = null)
    {
        if (isset($value)) {
            return $this->builder->whereHas('tags', function ($q) use ($value) {
                $q->where('slug', '=', $value);
            });
        } else {
            return $this->builder;
        }
    }

    public function role($value = null)
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
