<?php

namespace App\Services;

use App\Models\Event;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Turns an event's stored wall-clock times into real instants.
 *
 * events.start_at / end_at / door_at are stored naive and never converted on
 * the way in, so the value is a wall-clock reading with no zone attached. The
 * model casts it with config('app.timezone'), which is 'EST' — and PHP treats
 * 'EST' as a FIXED UTC-5 offset that never observes daylight saving. Pittsburgh
 * does: America/New_York is UTC-4 from March to November. So a Carbon taken
 * straight off the model names an instant an hour later than intended for most
 * of the year.
 *
 * That is invisible while the value is only ever formatted for display — 8 PM
 * is stored, 8 PM is printed, and the offset never comes up. It becomes wrong
 * the moment an absolute instant leaves the app and something else renders it:
 * a Discord <t:unix> tag, an iCal DTSTART. Those are the callers that belong
 * here.
 *
 * App\Services\Calendar\ICalBuilder and App\Enums\EventTimeWindow each worked
 * this out independently; this is the shared version. Deliberately free of
 * side effects — in particular it never calls date_default_timezone_set(),
 * which would change how the rest of the request reads dates.
 */
class EventTime
{
    /**
     * The zone the stored wall-clock values are written in. Never
     * config('app.timezone') — see the class docblock.
     */
    public const ZONE = 'America/New_York';

    /**
     * Reinterpret a naive wall-clock value as the instant it actually names.
     */
    public static function toInstant(DateTimeInterface|string|null $value): ?CarbonImmutable
    {
        if (null === $value) {
            return null;
        }

        // Reduce to the wall-clock reading first. A value arriving as the
        // model's cast Carbon already carries the wrong fixed-offset zone, so
        // converting it would preserve the error — the digits are the only
        // trustworthy part.
        $naive = $value instanceof DateTimeInterface
            ? $value->format('Y-m-d H:i:s')
            : trim($value);

        if ('' === $naive) {
            return null;
        }

        return CarbonImmutable::parse($naive, self::ZONE);
    }

    public static function startsAt(Event $event): ?CarbonImmutable
    {
        return self::toInstant($event->start_at);
    }

    public static function doorsAt(Event $event): ?CarbonImmutable
    {
        return self::toInstant($event->door_at);
    }

    /**
     * end_at when set, otherwise start plus Event::DEFAULT_LENGTH hours.
     *
     * Mirrors Event::getDefaultEndTimeAttribute(), NOT getEndTimeAttribute() —
     * the latter returns null with no fallback, which is the wrong answer for
     * any caller that must emit an end time.
     */
    public static function endsAt(Event $event): ?CarbonImmutable
    {
        return self::toInstant($event->end_at)
            ?? self::startsAt($event)?->addHours(Event::DEFAULT_LENGTH);
    }
}
