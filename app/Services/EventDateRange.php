<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Provides the date range covered by real event data, used to clamp
 * date-navigable listings (/events/upcoming/{date}, /events/add/{date})
 * so crawlers can't walk day-by-day into decades with no events.
 */
class EventDateRange
{
    protected const CACHE_KEY = 'event-date-range';
    protected const CACHE_TTL = 3600;

    /**
     * @return array{min: Carbon, max: Carbon}
     */
    public function bounds(): array
    {
        $bounds = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $row = Event::query()
                ->selectRaw('MIN(start_at) as min_start, MAX(start_at) as max_start')
                ->toBase()
                ->first();

            return [
                'min' => $row && $row->min_start ? (string) $row->min_start : null,
                'max' => $row && $row->max_start ? (string) $row->max_start : null,
            ];
        });

        return [
            'min' => $bounds['min'] ? Carbon::parse($bounds['min']) : Carbon::now(),
            'max' => $bounds['max'] ? Carbon::parse($bounds['max']) : Carbon::now(),
        ];
    }

    public function contains(Carbon $date): bool
    {
        $bounds = $this->bounds();

        return $date->between($bounds['min']->copy()->startOfDay(), $bounds['max']->copy()->endOfDay());
    }
}
