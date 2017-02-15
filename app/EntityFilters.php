<?php

namespace App;

class EntityFilters extends QueryFilter
{
	public function name($value) // example.com/events?ages
	{
		return $this->builder->where('name', $value);
	}


}