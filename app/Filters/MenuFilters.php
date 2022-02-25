<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class MenuFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('name', $value);
        } else {
            return $this->builder;
        }
    }

    public function slug(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('slug', $value);
        } else {
            return $this->builder;
        }
    }
}
