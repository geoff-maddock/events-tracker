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
 * the dates the stepping version produced, for every schedule shape.
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
}
