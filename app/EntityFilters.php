<?php

namespace App;

class EntityFilters extends QueryFilter
{
	public function name($value = NULL) // example.com/events?ages
	{
		if (isset($value))
		{
			return $this->builder->where('name', $value);
		} else {
			return $this->builder;
		}
	}

}