<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\OccurrenceType;
use App\Models\Series;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A Biweekly or Bimonthly series counts its "on" week or month from its latest
 * event, and from the founding date only until it has one (#2422).
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
    public function a_biweekly_series_counts_from_its_latest_event(): void
    {
        // founded Monday 2 November, at 20:00
        $series = $this->series('Biweekly', '2026-11-02 20:00:00', day: 2);
        $this->assertSame('2026-11-16 20:00', $this->nextOn('2026-11-10 12:00', $series));

        // it actually ran on Thursday the 12th: two weeks on from that, at the series' hour
        $this->eventOn($series, '2026-11-12 21:30:00');
        $this->assertSame('2026-11-26 20:00', $this->nextOn('2026-11-13 12:00', $series));
        $this->assertSame('2026-11-26 20:00', $this->nextOn('2026-11-26 09:00', $series));
        $this->assertSame('2026-12-10 20:00', $this->nextOn('2026-11-27 09:00', $series));

        // an older event doesn't pull the rhythm back, and one still to come isn't counted yet
        $this->eventOn($series, '2026-11-05 21:30:00');
        $this->eventOn($series, '2026-12-03 21:30:00');
        $this->assertSame('2026-11-26 20:00', $this->nextOn('2026-11-20 12:00', $series));
        // once that one has happened, it sets the rhythm
        $this->assertSame('2026-12-17 20:00', $this->nextOn('2026-12-04 12:00', $series));
    }

    #[Test]
    public function an_event_today_is_the_next_date(): void
    {
        $series = $this->series('Biweekly', '2026-11-02 20:00:00', day: 2);
        $this->eventOn($series, '2026-11-12 21:30:00');

        $this->assertSame('2026-11-12 20:00', $this->nextOn('2026-11-12 08:00', $series));
        $this->assertSame('2026-11-12 20:00', $this->nextOn('2026-11-12 23:30', $series));
    }

    #[Test]
    public function the_remembered_date_follows_a_new_event(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 12:00', 'America/New_York'));
        $series = $this->series('Biweekly', '2026-11-02 20:00:00', day: 2);

        $this->assertSame('2026-11-30', $series->cycleFromFoundedAt()->format('Y-m-d'));

        $this->eventOn($series, '2026-11-19 21:30:00');
        $this->assertSame('2026-12-03', $series->cycleFromFoundedAt()->format('Y-m-d'));
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
