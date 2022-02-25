<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ReviewFilters extends QueryFilter
{
    public function review(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('review', $value);
        } else {
            return $this->builder;
        }
    }

    public function user(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('user', function ($q) use ($value) {
                $q->where('name', '=', $value);
            });
        } else {
            return $this->builder;
        }
    }

    public function review_type(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('reviewType', function ($q) use ($value) {
                $q->where('name', '=', $value);
            });
        } else {
            return $this->builder;
        }
    }
}
