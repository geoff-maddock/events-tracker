<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class OccurrenceDayFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('occurrence_days.name', 'like', '%'.$value.'%');
        }

        return $this->builder;
    }
}
