<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class EntityTypeFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('name', $value);
        } else {
            return $this->builder;
        }
    }
}
