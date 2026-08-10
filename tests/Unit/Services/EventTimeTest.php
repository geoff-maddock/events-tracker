<?php

namespace Tests\Unit\Services;

use App\Models\Event;
use App\Services\EventTime;
use Tests\TestCase;

/**
 * Naive wall-clock values → real instants.
 *
 * The whole point of this class is the DST case. config('app.timezone') is
 * 'EST', a fixed UTC-5 offset that never observes daylight saving, so anything
 * deriving an instant from the model's cast Carbon is an hour late from March
 * to November. These tests pin the offsets on both sides of that boundary,
 * because a regression here is silent — it produces a perfectly well-formed
 * timestamp that simply names the wrong moment.
 */
class EventTimeTest extends TestCase
{
    private function event(?string $startAt, ?string $endAt = null, ?string $doorAt = null): Event
    {
        // Unsaved: the casts apply without touching the database.
        return new Event([
            'start_at' => $startAt,
            'end_at' => $endAt,
            'door_at' => $doorAt,
        ]);
    }

    public function test_a_summer_event_resolves_against_daylight_time(): void
    {
        $instant = EventTime::startsAt($this->event('2026-08-15 20:00:00'));

        $this->assertSame('2026-08-15T20:00:00-04:00', $instant->toIso8601String());
        $this->assertSame('2026-08-16T00:00:00+00:00', $instant->utc()->toIso8601String());
    }

    public function test_a_winter_event_resolves_against_standard_time(): void
    {
        $instant = EventTime::startsAt($this->event('2026-01-15 20:00:00'));

        $this->assertSame('2026-01-15T20:00:00-05:00', $instant->toIso8601String());
        $this->assertSame('2026-01-16T01:00:00+00:00', $instant->utc()->toIso8601String());
    }

    public function test_it_disagrees_with_the_models_cast_carbon_in_summer(): void
    {
        // The bug this class exists to fix, stated directly: the cast Carbon
        // and the correct instant are one hour apart for most of the year.
        $event = $this->event('2026-08-15 20:00:00');

        $this->assertSame(
            3600,
            $event->start_at->timestamp - EventTime::startsAt($event)->timestamp
        );
    }

    public function test_it_agrees_with_the_models_cast_carbon_in_winter(): void
    {
        $event = $this->event('2026-01-15 20:00:00');

        $this->assertSame($event->start_at->timestamp, EventTime::startsAt($event)->timestamp);
    }

    public function test_ends_at_uses_end_at_when_present(): void
    {
        $event = $this->event('2026-08-15 20:00:00', '2026-08-15 23:30:00');

        $this->assertSame('2026-08-15T23:30:00-04:00', EventTime::endsAt($event)->toIso8601String());
    }

    public function test_ends_at_falls_back_to_the_default_event_length(): void
    {
        $event = $this->event('2026-08-15 20:00:00');

        $this->assertSame(
            '2026-08-16T00:00:00-04:00',
            EventTime::endsAt($event)->toIso8601String()
        );
        $this->assertSame(Event::DEFAULT_LENGTH, 4);
    }

    public function test_doors_at_resolves_the_same_way(): void
    {
        $event = $this->event('2026-08-15 20:00:00', null, '2026-08-15 19:00:00');

        $this->assertSame('2026-08-15T19:00:00-04:00', EventTime::doorsAt($event)->toIso8601String());
    }

    public function test_missing_values_are_null_rather_than_now(): void
    {
        $event = $this->event(null);

        $this->assertNull(EventTime::startsAt($event));
        $this->assertNull(EventTime::doorsAt($event));
        $this->assertNull(EventTime::endsAt($event));
        $this->assertNull(EventTime::toInstant(null));
        $this->assertNull(EventTime::toInstant('   '));
    }

    public function test_it_accepts_a_raw_database_string(): void
    {
        $this->assertSame(
            '2026-08-15T20:00:00-04:00',
            EventTime::toInstant('2026-08-15 20:00:00')->toIso8601String()
        );
    }

    public function test_it_does_not_mutate_the_process_timezone(): void
    {
        // ICalBuilder used to call date_default_timezone_set() from inside the
        // builder, changing how the rest of the request read dates.
        $before = date_default_timezone_get();

        EventTime::startsAt($this->event('2026-08-15 20:00:00'));

        $this->assertSame($before, date_default_timezone_get());
    }
}
