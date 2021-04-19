<?php

namespace App\Filters;

class EntityTypeFilters extends QueryFilter
{
    public function name($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('name', $value);
        } else {
            return $this->builder;
        }
    }
}
