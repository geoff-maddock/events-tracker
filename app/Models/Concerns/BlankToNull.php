<?php

namespace App\Models\Concerns;

use Carbon\Carbon;

/**
 * Form fields left empty arrive as '' (or 0 for an unselected id), and those
 * must be stored as null, not as "now" or a foreign key of 0.
 *
 * A model lists the attributes this applies to in two constants:
 * BLANK_TO_NULL_DATES (anything else is parsed to a date) and
 * BLANK_TO_NULL_INTS (ids and other whole numbers). It replaces a
 * setXAttribute() mutator per attribute.
 */
trait BlankToNull
{
    public function setAttribute($key, $value)
    {
        if (in_array($key, static::BLANK_TO_NULL_DATES, true)) {
            $this->attributes[$key] = self::dateOrNull($value);

            return $this;
        }

        if (in_array($key, static::BLANK_TO_NULL_INTS, true)) {
            $this->attributes[$key] = self::intOrNull($value);

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    private static function dateOrNull(?string $date): ?Carbon
    {
        return empty($date) ? null : Carbon::parse($date);
    }

    private static function intOrNull(?int $value): ?int
    {
        return empty($value) ? null : $value;
    }
}
