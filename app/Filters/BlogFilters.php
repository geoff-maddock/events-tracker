<?php

namespace App\Filters;

class BlogFilters extends QueryFilter
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
