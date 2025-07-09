<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class RoleFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('roles.name', 'like', '%'.$value.'%');
        }

        return $this->builder;
    }
}
