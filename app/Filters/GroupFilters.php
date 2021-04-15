<?php

namespace App\Filters;

class GroupFilters extends QueryFilter
{
    public function name($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('groups.name', 'like', '%' . $value . '%');
        } else {
            return $this->builder;
        }
    }

    public function label($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('groups.label', 'like', '%' . $value . '%');
        } else {
            return $this->builder;
        }
    }

    public function level($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('groups.level', '=', $value);
        } else {
            return $this->builder;
        }
    }
}
