<?php

namespace App\Filters;

class EventFilters extends QueryFilter
{
    public function name($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('name', 'like', '%' . $value . '%');
        } else {
            return $this->builder;
        }
    }

    public function tag($value = null)
    {
        if (isset($value)) {
            return $this->builder->whereHas('tags', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function related($value = null)
    {
        if (isset($value)) {
            return $this->builder->whereHas('entities', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function event_type($value = null)
    {
        if (isset($value)) {
            return $this->builder->whereHas('eventType', function ($q) use ($value) {
                $q->where('name', '=', ucfirst($value));
            });
        } else {
            return $this->builder;
        }
    }

    public function start_at($value = null)
    {
        // if not an array, do not process

        if (isset($value)) {
            if (!is_array($value)) {
                return $this->builder;
            }

            if ($start = $value['start']) {
                $this->builder->whereDate('start_at', '>=', $start);
            }

            if ($end = $value['end']) {
                $this->builder->whereDate('start_at', '>=', $end);
            }

            return $this->builder;
        } else {
            return $this->builder;
        }
    }

    public function ages($order = 'desc') // example.com/events?ages
    {
        return $this->builder->orderBy('ages_id', $order);
    }
}
