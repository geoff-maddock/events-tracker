<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class OccurrenceWeekFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('occurrence_weeks.name', 'like', '%'.$value.'%');
        }

        return $this->builder;
    }
}
