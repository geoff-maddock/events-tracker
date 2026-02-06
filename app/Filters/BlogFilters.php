<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class BlogFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function body(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('body', 'like', '%'.$value.'%');
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

    public function tag(?string $value = null): Builder
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
