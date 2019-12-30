<?php

namespace App\Filters;

class MenuFilters extends QueryFilter
{
    public function name($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('name', $value);
        } else {
            return $this->builder;
        }
    }

    public function slug($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('slug', $value);
        } else {
            return $this->builder;
        }
    }
}
