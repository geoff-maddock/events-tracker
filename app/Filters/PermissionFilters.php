<?php

namespace App\Filters;

class PermissionFilters extends QueryFilter
{
    public function name($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('permissions.name', 'like', '%' . $value . '%');
        } else {
            return $this->builder;
        }
    }

    public function label($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('permissions.label', 'like', '%' . $value . '%');
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
