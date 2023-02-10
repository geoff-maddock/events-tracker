<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class LinkFilters extends QueryFilter
{
    public function url(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('links.url', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function text(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('links.text', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }
}
