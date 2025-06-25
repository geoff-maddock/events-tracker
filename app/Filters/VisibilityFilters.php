<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class VisibilityFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('name', 'like', '%'.$value.'%');
        }

        return $this->builder;
    }
}
