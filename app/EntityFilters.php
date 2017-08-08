<?php

namespace App;

class EntityFilters extends QueryFilter
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

	public function type($value = NULL) 
	{
		if (isset($value))
		{
			return $this->builder->where('entity_type', $value);
		} else {
			return $this->builder;
		}
	}


}