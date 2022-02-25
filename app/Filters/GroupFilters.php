<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class GroupFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('groups.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function label(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('groups.label', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function level(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('groups.level', '=', $value);
        } else {
            return $this->builder;
        }
    }
}
