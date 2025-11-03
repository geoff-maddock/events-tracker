<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ActivityFilters extends QueryFilter
{
    public function message(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('activities.message', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function user_id(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('activities.user_id', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function object_id(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('activities.object_id', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function user_id(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('activities.user_id', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function object_id(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('activities.object_id', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function object_table(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('activities.object_table', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function action(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('action', function ($q) use ($value) {
                $q->where('name', '=', $value);
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

    public function level(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('permissions.level', '=', $value);
        } else {
            return $this->builder;
        }
    }
}
