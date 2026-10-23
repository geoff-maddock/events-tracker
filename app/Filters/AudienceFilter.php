<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * The age and price filters the event lists and the calendar feed share
 * (#2361), so a list and its calendar agree on what "All Ages" or "$10 or
 * less" means. The option values are words rather than ages because an
 * All Ages value of "0" reads as empty and gets dropped along the way.
 */
class AudienceFilter
{
    /** @var array<string, string> */
    public const AGE_OPTIONS = [
        'all_ages' => 'All Ages',
        'under_21' => 'Under 21 welcome',
        '18_plus' => '18+',
        '21_plus' => '21+ only',
    ];

    /** @var array<array-key, string> PHP stores the dollar keys as ints */
    public const PRICE_OPTIONS = [
        'free' => 'Free',
        '10' => '$10 or less',
        '20' => '$20 or less',
        '30' => '$30 or less',
    ];

    /**
     * Limit $query to the age policy named by $value; an unknown value leaves
     * it alone. Events with no age set never match.
     *
     * @template TBuilder of Builder
     *
     * @param TBuilder $query
     *
     * @return TBuilder
     */
    public static function applyAge(Builder $query, string $column, ?string $value): Builder
    {
        return match ($value) {
            'all_ages' => $query->where($column, '=', 0),
            'under_21' => $query->where($column, '<', 21),
            '18_plus' => $query->where($column, '=', 18),
            '21_plus' => $query->where($column, '=', 21),
            default => $query,
        };
    }

    /**
     * Limit $query to free entry ("free", a door price of 0, as PriceLabel
     * shows it) or to a door price at or under a dollar amount. Events with no
     * door price never match.
     *
     * @template TBuilder of Builder
     *
     * @param TBuilder $query
     *
     * @return TBuilder
     */
    public static function applyPrice(Builder $query, string $column, ?string $value): Builder
    {
        if ($value === 'free') {
            return $query->where($column, '=', 0);
        }

        if (is_numeric($value) && (float) $value >= 0) {
            return $query->where($column, '<=', (float) $value);
        }

        return $query;
    }
}
