<?php

namespace App\Filters;

use Illuminate\Http\Request;

abstract class Filters
{
    protected $filters = [];

    protected $request;

    protected $builder;

    /**
     * Filters constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param $builder
     *
     * @return mixed
     */
    public function apply($builder)
    {
        $this->builder = $builder;

        foreach ($this->getFilters() as $filter => $value) {
            if (method_exists($this, $filter)) {
                $this->$filter($value);
            }
        }

        return $this->builder;
    }

    /**
     * @return mixed
     */
    public function getFilters()
    {
        return $this->request->intersect($this->filters);
    }

    /**
     * @param $filter
     *
     * @return bool
     */
    protected function hasFilter($filter)
    {
        return method_exists($this, $filter) && $this->request->has($filter);
    }
}
