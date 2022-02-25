<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class PermissionFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('permissions.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function label(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('permissions.label', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function level(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('permissions.level', '=', $value);
        } else {
            return $this->builder;
        }
    }
}
