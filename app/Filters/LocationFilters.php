<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class LocationFilters extends QueryFilter
{
    public function name(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.name', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function neighborhood(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.neighborhood', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function slug(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.slug', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function attn(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.attn', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function addressOne(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.address_one', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function addressTwo(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.address_two', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function city(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.city', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function state(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.state', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function postcode(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.postcode', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function country(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.country', '=', $value);
        } else {
            return $this->builder;
        }
    }

    // Numeric filters take the raw query-string value and ignore anything that
    // isn't a number: a typed ?int/?float parameter threw a TypeError (a 500)
    // on input like filters[capacity]=abc or a repeated filters[capacity][].
    public function latitude(?string $value = null): Builder
    {
        $number = filter_var($value, FILTER_VALIDATE_FLOAT);

        return $number === false ? $this->builder : $this->builder->where('locations.latitude', '=', $number);
    }

    public function longitude(?string $value = null): Builder
    {
        $number = filter_var($value, FILTER_VALIDATE_FLOAT);

        return $number === false ? $this->builder : $this->builder->where('locations.longitude', '=', $number);
    }

    public function locationTypeId(?string $value = null): Builder
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);

        return $number === false ? $this->builder : $this->builder->where('locations.location_type_id', '=', $number);
    }

    public function visibilityId(?string $value = null): Builder
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);

        return $number === false ? $this->builder : $this->builder->where('locations.visibility_id', '=', $number);
    }

    public function entityId(?string $value = null): Builder
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);

        return $number === false ? $this->builder : $this->builder->where('locations.entity_id', '=', $number);
    }

    public function capacity(?string $value = null): Builder
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);

        return $number === false ? $this->builder : $this->builder->where('locations.capacity', '=', $number);
    }

    public function mapUrl(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.map_url', 'like', '%'.$value.'%');
        } else {
            return $this->builder;
        }
    }

    public function search(?string $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where(function ($query) use ($value) {
                $query->where('locations.name', 'like', '%'.$value.'%')
                    ->orWhere('locations.address_one', 'like', '%'.$value.'%')
                    ->orWhere('locations.city', 'like', '%'.$value.'%')
                    ->orWhere('locations.neighborhood', 'like', '%'.$value.'%')
                    ->orWhere('locations.country', 'like', '%'.$value.'%');
            });
        } else {
            return $this->builder;
        }
    }

}
