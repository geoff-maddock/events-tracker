<?php

namespace App\Services\Calendar;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * The date window a calendar JSON feed is asked for.
 *
 * FullCalendar sends `start`/`end` as ISO 8601 strings with an offset. They are
 * parsed, moved into the app timezone (event times are stored in it), and the
 * span is capped so a caller cannot ask for every event ever with one request.
 */
final class CalendarRange
{
    public const MAX_DAYS = 100;

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function fromRequest(Request $request): array
    {
        $start = self::parse($request->query('start')) ?? Carbon::now()->startOfMonth();
        $end = self::parse($request->query('end'));

        if ($end === null || $end->lt($start)) {
            $end = $start->copy()->endOfMonth();
        }

        if ($start->diffInDays($end) > self::MAX_DAYS) {
            $end = $start->copy()->addDays(self::MAX_DAYS);
        }

        return [$start, $end];
    }

    private static function parse(mixed $value): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}
