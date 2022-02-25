<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class PostFilters extends QueryFilter
{
    public function body(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('posts.body', 'like', '%'.$value.'%');
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

    public function series(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('series', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
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

    public function related(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('entities', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }
}
