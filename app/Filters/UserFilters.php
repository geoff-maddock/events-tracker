<?php

namespace App\Filters;

class UserFilters extends QueryFilter
{
	public function name($value = NULL) // example.com/entity?name=<value>
	{
		if (isset($value))
		{
			return $this->builder->where('name', $value);
		} else {
			return $this->builder;
		}
	}

	public function status($value = NULL)
	{
		if (isset($value))
		{
			return $this->builder->where('status', $value);
		} else {
			return $this->builder;
		}
	}


}