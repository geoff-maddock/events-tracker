<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ThreadFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('threads.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function thread_category(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('threadCategory', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
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

    protected function popular(): Builder
    {
        $this->builder->getQuery()->orders = [];

        return $this->builder->orderBy('posts_count', 'desc');
    }

    public function series(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('series', function ($q) use ($value) {
                $q->where('slug', '=', $value);
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

    public function category(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('threadCategory', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
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
