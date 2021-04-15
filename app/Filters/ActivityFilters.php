<?php

namespace App\Filters;

class ActivityFilters extends QueryFilter
{
    public function message($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('activities.message', 'like', '%' . $value . '%');
        } else {
            return $this->builder;
        }
    }

    public function object_table($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('activities.object_table', 'like', '%' . $value . '%');
        } else {
            return $this->builder;
        }
    }

    public function action($value = null)
    {
        if (isset($value)) {
            return $this->builder->whereHas('action', function ($q) use ($value) {
                $q->where('name', '=', $value);
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

    public function level($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('permissions.level', '=', $value);
        } else {
            return $this->builder;
        }
    }
}
