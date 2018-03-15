<?php

namespace App\Filters;

class EventFilters extends QueryFilter
{
	public function ages($order = 'desc') // example.com/events?ages
	{
		return $this->builder->orderBy('ages_id', $order);
	}


}