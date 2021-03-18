<?php

namespace App\Filters;

class UserFilters extends QueryFilter
{
    public function name($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('users.name', 'like', '%' . $value . '%');
        } else {
            return $this->builder;
        }
    }

    public function status($value = null)
    {
        if (isset($value)) {
            return $this->builder->whereHas('status', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
            ;
        } else {
            return $this->builder;
        }
    }
}
