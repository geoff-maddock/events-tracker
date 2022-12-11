<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class TagFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('tags.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }


    public function tag_type(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('tagType', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }
}
