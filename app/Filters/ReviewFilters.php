<?php

namespace App\Filters;

class ReviewFilters extends QueryFilter
{
    public function review($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('review', $value);
        } else {
            return $this->builder;
        }
    }

    public function user($value = null)
    {
        if (isset($value)) {
            return $this->builder->whereHas('user', function ($q) use ($value) {
                $q->where('name', '=', $value);
            });
        } else {
            return $this->builder;
        }
    }
}
