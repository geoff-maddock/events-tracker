<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventStatus;
use App\Models\OccurrenceType;
use App\Models\Series;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A Biweekly or Bimonthly series takes its "on" week or month from its latest
 * event, and from the founding date until it has one. The weekday and the
 * time stay the series' own (#2422).
 */
class SeriesRhythmAnchorTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function series(string $type, string $founded, ?int $week = null, ?int $day = null): Series
    {
        return Series::factory()->create([
            'occurrence_type_id' => OccurrenceType::where('name', $type)->firstOrFail()->id,
            'occurrence_week_id' => $week,
            'occurrence_day_id' => $day,
            'founded_at' => $founded,
            'cancelled_at' => null,
        ]);
    }

    private function eventOn(Series $series, string $startAt): Event
    {
        return Event::factory()->create(['series_id' => $series->id, 'start_at' => $startAt]);
    }

    private function nextOn(string $now, Series $series): string
    {
        Carbon::setTestNow(Carbon::parse($now, 'America/New_York'));

        return $series->fresh()->cycleFromFoundedAt()->format('Y-m-d H:i');
    }

    #[Test]
    public function a_biweekly_series_takes_its_on_week_from_its_latest_event(): void
    {
        // founded Monday 2 November at 20:00: by that alone, the 16th and the 30th
        $series = $this->series('Biweekly', '2026-11-02 20:00:00', day: 2);
        $this->assertSame('2026-11-16 20:00', $this->nextOn('2026-11-10 12:00', $series));

        // it actually ran on Thursday the 12th, so the week of the 9th was "on":
        // the Monday two weeks after that one, still at the series' hour
        $this->eventOn($series, '2026-11-12 21:30:00');
        $this->assertSame('2026-11-23 20:00', $this->nextOn('2026-11-13 12:00', $series));
        $this->assertSame('2026-11-23 20:00', $this->nextOn('2026-11-23 09:00', $series));
        $this->assertSame('2026-12-07 20:00', $this->nextOn('2026-11-24 09:00', $series));

        // an older event doesn't pull the rhythm back, and one still to come isn't counted yet
        $this->eventOn($series, '2026-11-05 21:30:00');
        $this->eventOn($series, '2026-12-03 21:30:00');
        $this->assertSame('2026-11-23 20:00', $this->nextOn('2026-11-20 12:00', $series));
        // once that one has happened (the week of 30 November), it sets the rhythm
        $this->assertSame('2026-12-14 20:00', $this->nextOn('2026-12-04 12:00', $series));
    }

    #[Test]
    public function todays_event_and_cancelled_ones_do_not_set_the_rhythm(): void
    {
        $series = $this->series('Biweekly', '2026-11-02 20:00:00', day: 2);
        $event = $this->eventOn($series, '2026-11-12 21:30:00');

        // on the day itself the event is the series' next event; the schedule is unmoved
        $this->assertSame('2026-11-16 20:00', $this->nextOn('2026-11-12 08:00', $series));
        $this->assertSame('2026-11-16 20:00', $this->nextOn('2026-11-12 23:30', $series));
        $this->assertSame('2026-11-23 20:00', $this->nextOn('2026-11-13 08:00', $series));

        // a night that was called off never happened
        $event->update(['cancelled_at' => '2026-11-11 10:00:00']);
        $this->assertSame('2026-11-16 20:00', $this->nextOn('2026-11-13 08:00', $series));

        $event->update(['cancelled_at' => null, 'event_status_id' => EventStatus::CANCELLED]);
        $this->assertSame('2026-11-16 20:00', $this->nextOn('2026-11-13 08:00', $series));
    }

    #[Test]
    public function a_late_night_event_counts_on_its_own_date_in_summer_time(): void
    {
        // Friday 3 July at 23:30, stored as wall-clock time; "today" is New York's.
        // The week of Monday 29 June was on, so Monday 13 July is next.
        $series = $this->series('Biweekly', '2026-06-01 20:00:00', day: 2);
        $this->eventOn($series, '2026-07-03 23:30:00');

        $this->assertSame('2026-07-13 20:00', $this->nextOn('2026-07-04 00:30', $series));
        $this->assertSame('2026-07-13 20:00', $this->nextOn('2026-07-04 18:00', $series));
    }

    #[Test]
    public function the_latest_event_is_looked_up_once_a_day_per_series(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 12:00', 'America/New_York'));
        $series = $this->series('Biweekly', '2026-11-02 20:00:00', day: 2);
        $this->eventOn($series, '2026-11-12 21:30:00');
        $series = $series->fresh(['occurrenceType']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $series->cycleFromFoundedAt();
        $series->nextOccurrenceDate();
        $series->nextOccurrenceEndDate();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $queries);

        // a new day asks again
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:00', 'America/New_York'));
        $this->assertSame('2026-12-07', $series->cycleFromFoundedAt()->format('Y-m-d'));
    }

    #[Test]
    public function a_bimonthly_series_counts_months_from_its_latest_event(): void
    {
        // second Wednesday, founded in January: March, May... by the founding date
        $series = $this->series('Bimonthly', '2026-01-05 19:00:00', week: 2, day: 4);
        $this->assertSame('2026-05-13 19:00', $this->nextOn('2026-04-20 12:00', $series));

        // it ran in April: June is next, then August
        $this->eventOn($series, '2026-04-08 19:00:00');
        $this->assertSame('2026-06-10 19:00', $this->nextOn('2026-04-20 12:00', $series));
        $this->assertSame('2026-08-12 19:00', $this->nextOn('2026-06-11 12:00', $series));
    }

    #[Test]
    public function the_other_schedules_ignore_events(): void
    {
        $weekly = $this->series('Weekly', '2026-11-02 20:00:00', day: 2);
        $this->eventOn($weekly, '2026-11-12 21:30:00');
        $this->assertSame('2026-11-16 20:00', $this->nextOn('2026-11-13 12:00', $weekly));

        $monthly = $this->series('Monthly', '2026-01-05 19:00:00', week: 2, day: 4);
        $this->eventOn($monthly, '2026-04-20 19:00:00');
        $this->assertSame('2026-05-13 19:00', $this->nextOn('2026-04-21 12:00', $monthly));
    }
}
