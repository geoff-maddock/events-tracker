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

    public function latitude(?float $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.latitude', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function longitude(?float $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.longitude', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function locationTypeId(?int $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.location_type_id', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function visibilityId(?int $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.visibility_id', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function entityId(?int $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.entity_id', '=', $value);
        } else {
            return $this->builder;
        }
    }

    public function capacity(?int $value = null): Builder
    {
        if (isset($value)) {
            return $this->builder->where('locations.capacity', '=', $value);
        } else {
            return $this->builder;
        }
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
