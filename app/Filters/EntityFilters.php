<?php

namespace App\Filters;

class EntityFilters extends QueryFilter
{
    public function name($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('name', $value);
        } else {
            return $this->builder;
        }
    }

    public function type($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('entity_type', $value);
        } else {
            return $this->builder;
        }
    }
}
