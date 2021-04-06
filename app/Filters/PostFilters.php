<?php

namespace App\Filters;

class PostFilters extends QueryFilter
{
    public function body($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('posts.body', 'like', '%' . $value . '%');
        } else {
            return $this->builder;
        }
    }

    public function tag($value = null)
    {
        if (isset($value)) {
            return $this->builder->whereHas('tags', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function series($value = null)
    {
        if (isset($value)) {
            return $this->builder->whereHas('series', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
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

    public function related($value = null)
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
