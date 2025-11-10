<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class UserFilters extends QueryFilter
{
    public function email(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('users.email', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('users.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function status(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->whereHas('status', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function is_verified(?string $value = null): Builder
    {
        if (isset($value)) {
            // where not null
            return $this->builder->where('users.email_verified_at', '!=', null);
        } else {
            return $this->builder;
        }
    }
}
