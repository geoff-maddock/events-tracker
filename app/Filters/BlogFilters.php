<?php

namespace App\Filters;

class BlogFilters extends QueryFilter
{
    public function name($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('name', $value);
        } else {
            return $this->builder;
        }
    }

    public function body($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('body', $value);
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

    public function tag($value = null)
    {
        if (isset($value)) {
            return $this->builder->whereHas('tags', function ($q) use ($value) {
                $q->where('slug', '=', $value);
            });
        } else {
            return $this->builder;
        }
    }
}
