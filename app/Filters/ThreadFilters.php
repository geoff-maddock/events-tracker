<?php

namespace App\Filters;


class ThreadFilters extends Filters
{

    protected $filters = ['by'];

    protected function by($username)
    {
        return $this->builder->whereHas('user', function ($q) use ($username) {
            $q->where('name', '=', $username);
        });
    }
}