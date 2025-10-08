<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class TagFilters extends QueryFilter
{
    public function id(?string $value = null): Builder
    {

        $values = is_array($value) ? $value : array_filter(explode(',', (string) $value));


        if (count($values) > 1) {
            return $this->builder->whereIn('tags.id', $values);
        }

        if (isset($value)) {
            return $this->builder->where('tags.id', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('tags.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function description(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('tags.description', 'like', '%'.$value.'%');
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
