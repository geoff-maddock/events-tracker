<?php

namespace App\Filters;

class ForumFilters extends QueryFilter
{
    public function name($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('forums.name', 'like', '%' . $value . '%');
        } else {
            return $this->builder;
        }
    }
}
