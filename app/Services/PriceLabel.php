<?php

namespace App\Services;

/**
 * How a ticket price is shown: nothing when none was entered, "Free" for 0,
 * and dollars otherwise ("$12", "$12.50"). Prices come back from the
 * decimal(5,2) columns as strings such as "0.00", which PHP treats as truthy,
 * so views must not test the raw value (#2261).
 */
class PriceLabel
{
    public static function for(mixed $price): ?string
    {
        if (null === $price || '' === $price || !is_numeric($price)) {
            return null;
        }

        $value = (float) $price;
        if (0.0 === $value) {
            return 'Free';
        }

        return '$'.(floor($value) == $value ? number_format($value, 0) : number_format($value, 2));
    }
}
