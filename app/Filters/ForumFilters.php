<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ForumFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('forums.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }
}
