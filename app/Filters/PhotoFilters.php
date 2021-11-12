<?php

namespace App\Filters;

class PhotoFilters extends QueryFilter
{
    public function name($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('events.name', 'like', '%' . $value . '%');
        } else {
            return $this->builder;
        }
    }

    public function is_primary($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('photos.is_primary', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function is_event($value = null)
    {
        if (isset($value)) {
            return $this->builder->where('photos.is_event', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function created_at($value = null)
    {
        // if not an array, do not process

        if (isset($value)) {
            if (!is_array($value)) {
                return $this->builder;
            }

            if (isset($value['start']) && $start = $value['start']) {
                $this->builder->whereDate('created_at', '>=', $start);
            }

            if (isset($value['end']) && $end = $value['end']) {
                $this->builder->whereDate('created_at', '<=', $end);
            }

            return $this->builder;
        } else {
            return $this->builder;
        }
    }
}
