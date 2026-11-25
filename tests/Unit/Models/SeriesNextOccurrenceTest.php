<?php

namespace Tests\Unit\Models;

use App\Models\OccurrenceType;
use App\Models\Series;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Series::cycleFromFoundedAt() used to step from the founding date to today one
 * period at a time (#2388). It now jumps most of the way; these tests hold it to
 * the dates stepping produces, for every schedule shape, and to the steps
 * themselves being right (#2422).
 */
class SeriesNextOccurrenceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function series(string $type, ?int $week, ?int $day, ?string $founded, string $created = '2019-03-05 12:00:00'): Series
    {
        $series = new Series();
        $series->setRawAttributes([
            'founded_at' => $founded,
            'created_at' => $created,
            'occurrence_week_id' => $week,
            'occurrence_day_id' => $day,
            'length' => 3,
        ]);
        $series->setRelation('occurrenceType', new OccurrenceType(['name' => $type]));

        return $series;
    }

    /** The loop as it was before #2388, stepping with the model's own cycleForward(). */
    private function steppedFromFounding(Series $series): Carbon
    {
        $start = $series->founded_at ?: $series->created_at;
        $next = $start;

        while ($next < Carbon::now('America/New_York')->startOfDay()) {
            $next = $series->cycleForward($next);
        }

        $next->setHour($start->hour);
        $next->setMinute($start->minute);

        return $next;
    }

    /** @return array<string, array{string, int|null, int|null}> */
    public static function schedules(): array
    {
        $schedules = [
            'no schedule' => ['No Schedule', null, null],
            'no schedule with a day' => ['No Schedule', 3, 6],
            'weekly' => ['Weekly', null, 6],
            'weekly without a day' => ['Weekly', null, null],
            'biweekly' => ['Biweekly', null, 2],
            'biweekly with a week' => ['Biweekly', 2, 5],
            'bimonthly' => ['Bimonthly', 2, 4],
            'yearly' => ['Yearly', null, null],
        ];

        foreach ([1, 2, 3, 4, 5] as $week) {
            foreach ([1, 6, 7] as $day) {
                $schedules["monthly week $week day $day"] = ['Monthly', $week, $day];
            }
        }

        // every weekday, for one week of the month
        foreach ([2, 3, 4, 5] as $day) {
            $schedules["monthly week 2 day $day"] = ['Monthly', 2, $day];
        }

        return $schedules;
    }

    #[DataProvider('schedules')]
    public function test_next_date_matches_stepping_from_the_founding_date(string $type, ?int $week, ?int $day): void
    {
        $founded = [
            '2016-02-29 19:30:00',
            '2019-01-31 22:00:00',
            '2023-12-31 23:59:00',
            '2026-11-10 00:00:00',
            '2027-01-02 20:00:00', // still in the future on most of the days below
            null,                  // falls back to created_at
        ];
        $todays = [
            '2026-01-31 23:30:00', '2026-02-01 00:10:00', '2026-07-04 04:30:00',
            '2026-11-19 15:00:00', '2027-01-01 00:00:00', '2028-02-29 08:00:00',
        ];

        foreach ($todays as $today) {
            Carbon::setTestNow(Carbon::parse($today, 'UTC'));

            foreach ($founded as $date) {
                // a yearly series is counted from its founding date, where stepping
                // loses a 29 February for good: see the leap-day test below (#2422)
                if ('Yearly' === $type && '2016-02-29 19:30:00' === $date) {
                    continue;
                }

                // the reference loop takes a step a day here: keep it to a few years
                if ('No Schedule' === $type && null !== $date && $date < '2023') {
                    continue;
                }

                $expected = $this->steppedFromFounding($this->series($type, $week, $day, $date));
                $actual = $this->series($type, $week, $day, $date)->cycleFromFoundedAt();

                $label = "founded ".($date ?? 'null').", now $today";
                $this->assertSame($expected->format('Y-m-d H:i:s T'), $actual->format('Y-m-d H:i:s T'), $label);
                $this->assertSame($expected->getTimestamp(), $actual->getTimestamp(), $label);
            }
        }
    }

    private function nextOn(string $now, Series $series): string
    {
        Carbon::setTestNow(Carbon::parse($now, 'America/New_York'));

        return $series->cycleFromFoundedAt()->format('Y-m-d H:i');
    }

    public function test_the_last_weekday_of_the_month_does_not_skip_a_short_month(): void
    {
        // last Friday, founded on Friday 31 January 2025: a month added to the
        // 31st ran into March and February was never offered (#2422)
        $series = fn () => $this->series('Monthly', 5, 6, '2025-01-31 21:00:00');

        $this->assertSame('2025-02-28 21:00', $this->nextOn('2025-02-10 12:00', $series()));
        $this->assertSame('2025-02-28 21:00', $this->nextOn('2025-02-28 12:00', $series()));
        $this->assertSame('2025-03-28 21:00', $this->nextOn('2025-03-01 12:00', $series()));
        $this->assertSame('2025-02-28', $series()->cycleForward(Carbon::parse('2025-01-31 21:00:00'))->format('Y-m-d'));
        // a five-Friday month: the last one, not the fourth
        $this->assertSame('2025-05-30 21:00', $this->nextOn('2025-05-24 12:00', $series()));
        $this->assertSame('2025-06-27', $series()->cycleForward(Carbon::parse('2025-05-30 21:00:00'))->format('Y-m-d'));
    }

    public function test_a_founding_date_at_the_end_of_a_month_does_not_skip_the_next_one(): void
    {
        // first Thursday, founded 31 January: the first step must land in February
        $this->assertSame('2025-02-06 20:00', $this->nextOn('2025-02-01 09:00', $this->series('Monthly', 1, 5, '2025-01-31 20:00:00')));
        // third Saturday, founded 30 January in a leap year
        $this->assertSame('2028-02-19 20:00', $this->nextOn('2028-02-01 09:00', $this->series('Monthly', 3, 7, '2028-01-30 20:00:00')));
        // and the founding month itself is still not offered
        $this->assertSame('2025-02-06 20:00', $this->nextOn('2025-01-31 23:00', $this->series('Monthly', 1, 5, '2025-01-30 20:00:00')));
    }

    public function test_a_yearly_series_founded_on_a_leap_day_keeps_it(): void
    {
        $series = fn () => $this->series('Yearly', null, null, '2016-02-29 19:30:00');

        $this->assertSame('2027-02-28 19:30', $this->nextOn('2027-01-10 12:00', $series()));
        $this->assertSame('2028-02-29 19:30', $this->nextOn('2027-03-01 12:00', $series()));
        $this->assertSame('2028-02-29 19:30', $this->nextOn('2028-02-29 08:00', $series()));
        $this->assertSame('2029-02-28 19:30', $this->nextOn('2028-03-01 08:00', $series()));
        // an ordinary date is the same day every year
        $this->assertSame('2027-06-14 21:00', $this->nextOn('2026-11-19 12:00', $this->series('Yearly', null, null, '2005-06-14 21:00:00')));
    }

    public function test_biweekly_and_bimonthly_take_two_periods_a_step(): void
    {
        // founded Monday 2 November 2026: on the 16th and 30th, not the 9th or 23rd
        $biweekly = fn () => $this->series('Biweekly', null, 2, '2026-11-02 20:00:00');
        $this->assertSame('2026-11-16 20:00', $this->nextOn('2026-11-03 12:00', $biweekly()));
        $this->assertSame('2026-11-16 20:00', $this->nextOn('2026-11-16 23:00', $biweekly()));
        $this->assertSame('2026-11-30 20:00', $this->nextOn('2026-11-17 12:00', $biweekly()));
        $this->assertSame('2030-01-07 20:00', $this->nextOn('2029-12-25 12:00', $biweekly()));

        // second Wednesday, founded in January: March, May, July...
        $bimonthly = fn () => $this->series('Bimonthly', 2, 4, '2026-01-05 19:00:00');
        $this->assertSame('2026-03-11 19:00', $this->nextOn('2026-01-20 12:00', $bimonthly()));
        $this->assertSame('2026-05-13 19:00', $this->nextOn('2026-03-12 12:00', $bimonthly()));
        $this->assertSame('2027-01-13 19:00', $this->nextOn('2026-11-25 12:00', $bimonthly()));
    }

    public function test_the_result_is_remembered_but_safe_to_change(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 15:00:00', 'UTC'));
        $series = $this->series('Weekly', null, 6, '2020-01-03 21:00:00');

        $first = $series->cycleFromFoundedAt();
        $expected = $first->format('Y-m-d H:i');
        $first->addYears(3);

        $this->assertSame($expected, $series->cycleFromFoundedAt()->format('Y-m-d H:i'));
        // nextOccurrenceEndDate() adds the length to the date it is given
        $series->nextOccurrenceEndDate();
        $this->assertSame($expected, $series->nextOccurrenceDate()->format('Y-m-d H:i'));
    }

    public function test_the_remembered_date_follows_the_clock_and_the_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 15:00:00', 'UTC'));
        $series = $this->series('Weekly', null, 6, '2020-01-03 21:00:00');
        $this->assertSame('2026-11-20', $series->cycleFromFoundedAt()->format('Y-m-d'));

        Carbon::setTestNow(Carbon::parse('2026-11-25 15:00:00', 'UTC'));
        $this->assertSame('2026-11-27', $series->cycleFromFoundedAt()->format('Y-m-d'));

        $series->setAttribute('founded_at', '2020-01-04 21:00:00');
        $this->assertSame('2026-11-28', $series->cycleFromFoundedAt()->format('Y-m-d'));
    }

    public function test_a_long_running_unscheduled_series_takes_a_handful_of_steps(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 15:00:00', 'UTC'));

        $series = new class extends Series {
            public int $steps = 0;

            public function cycleForward(?\DateTime $date): Carbon
            {
                $this->steps++;

                return parent::cycleForward($date);
            }
        };
        $series->setRawAttributes(['founded_at' => '2005-06-14 21:00:00']);
        $series->setRelation('occurrenceType', new OccurrenceType(['name' => 'No Schedule']));

        $this->assertSame('2026-11-19 21:00', $series->cycleFromFoundedAt()->format('Y-m-d H:i'));
        $this->assertLessThanOrEqual(3, $series->steps);
    }

    public function test_planned_start_is_empty_without_a_schedule_or_once_cancelled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 15:00:00', 'UTC'));

        $weekly = $this->series('Weekly', null, 6, '2020-01-03 21:00:00');
        $weekly->setRelation('upcomingEvent', null);
        $this->assertSame('2026-11-20', $weekly->nextPlannedStartAt()?->format('Y-m-d'));

        $unscheduled = $this->series('No Schedule', null, null, '2020-01-03 21:00:00');
        $unscheduled->setRelation('upcomingEvent', null);
        $this->assertNull($unscheduled->nextPlannedStartAt());

        $cancelled = $this->series('Weekly', null, 6, '2020-01-03 21:00:00');
        $cancelled->setRelation('upcomingEvent', null);
        $cancelled->setAttribute('cancelled_at', '2026-10-01 00:00:00');
        $this->assertNull($cancelled->nextPlannedStartAt());
    }

    public function test_a_series_without_an_occurrence_type_still_gets_a_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 15:00:00', 'UTC'));

        $series = $this->series('Weekly', null, null, '2027-01-02 20:00:00');
        $series->setRelation('occurrenceType', null);

        $this->assertSame('2027-01-02 20:00', $series->cycleFromFoundedAt()->format('Y-m-d H:i'));
    }
}
