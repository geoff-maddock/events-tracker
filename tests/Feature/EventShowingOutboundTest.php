<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Series;
use App\Models\Visibility;
use App\Services\Calendar\ICalBuilder;
use App\Services\EventSchema;
use App\Services\EventTime;
use App\Services\RssFeed;
use App\Services\SeriesSchema;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * JSON-LD, iCal, RSS and calendar links carry every date of a short run
 * (#2375).
 */
class EventShowingOutboundTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** Opened Mar 1; also plays Mar 4 and Mar 6 (2-4PM). "Now" is Mar 3 at noon. */
    private Event $run;

    private Event $single;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Carbon::setTestNow('2027-03-03 12:00:00');

        $this->run = $this->event('ZZ Run Play', '2027-03-01 20:00:00');
        $this->run->syncShowings([
            ['start_at' => '2027-03-04 19:00:00'],
            ['start_at' => '2027-03-06 14:00:00', 'end_at' => '2027-03-06 16:00:00'],
        ]);
        $this->run = $this->run->fresh();
        $this->single = $this->event('ZZ Friday Gig', '2027-03-05 20:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function event(string $name, string $startAt): Event
    {
        return Event::factory()->create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => $startAt,
            'end_at' => null,
            'door_at' => null,
            'cancelled_at' => null,
        ])->fresh();
    }

    public function test_json_ld_spans_the_run_and_lists_upcoming_showings(): void
    {
        $document = EventSchema::document($this->run);

        $this->assertSame('2027-03-01T20:00:00-05:00', $document['startDate']);
        // the end of the last showing
        $this->assertSame('2027-03-06T16:00:00-05:00', $document['endDate']);

        // the opening night has passed; the rest are subEvents
        $this->assertSame(
            ['2027-03-04T19:00:00-05:00', '2027-03-06T14:00:00-05:00'],
            array_column($document['subEvent'], 'startDate')
        );
        $this->assertSame('2027-03-04T23:00:00-05:00', $document['subEvent'][0]['endDate']);
        $this->assertSame('ZZ Run Play', $document['subEvent'][0]['name']);
        $this->assertArrayHasKey('location', $document['subEvent'][0]);

        $single = EventSchema::document($this->single);
        $this->assertArrayNotHasKey('subEvent', $single);
        $this->assertSame('2027-03-06T00:00:00-05:00', $single['endDate']);

        $this->get('/events/'.$this->run->slug)->assertOk()->assertSee('"subEvent"', false);
    }

    public function test_ical_has_an_entry_per_showing(): void
    {
        $ics = @(new ICalBuilder())->buildCalendar('run.ics', collect([$this->run, $this->single]));
        $ics = str_replace("\r\n ", '', $ics); // unfold long lines

        $this->assertSame(4, substr_count($ics, 'BEGIN:VEVENT'));
        $this->assertStringContainsString('20270301T200000', $ics);
        $this->assertStringContainsString('20270304T190000', $ics);
        $this->assertStringContainsString('20270306T140000', $ics);
        $this->assertStringContainsString('20270306T160000', $ics);

        // the first date keeps the event's own UID; the others add their start
        $uid = route('events.show', ['event' => $this->run]);
        $this->assertStringContainsString("UID:{$uid}\r\n", $ics);
        $this->assertStringContainsString("UID:{$uid}#20270304T190000\r\n", $ics);
        $this->assertStringContainsString("UID:{$uid}#20270306T140000\r\n", $ics);
    }

    public function test_the_rss_item_shows_the_next_date_and_the_rest(): void
    {
        $rss = app(RssFeed::class)->getRSS();

        $this->assertStringContainsString('Thursday March 4th 2027', $rss);
        $this->assertStringContainsString('Showings: Thu Mar 4 7PM, Sat Mar 6 2PM', $rss);
        $this->assertStringContainsString('Friday March 5th 2027', $rss);
    }

    public function test_each_upcoming_showing_has_its_own_calendar_link(): void
    {
        $finale = $this->run->occurrences()->last();
        $this->assertStringContainsString('dates=20270306T140000/20270306T160000', (string) $this->run->getGoogleCalendarLink($finale));
        // by default, the date the page leads with
        $this->assertStringContainsString('dates=20270301T200000/20270301T200000', (string) $this->run->getGoogleCalendarLink());
        // and with the event's own end, the same as that date's showing link
        $this->run->update(['end_at' => '2027-03-01 23:00:00']);
        $run = $this->run->fresh();
        $this->assertStringContainsString('dates=20270301T200000/20270301T230000', (string) $run->getGoogleCalendarLink());
        $this->assertSame($run->getGoogleCalendarLink(), $run->getGoogleCalendarLink($run->occurrences()->first()));
        $this->run->update(['end_at' => null]);

        $page = $this->get('/events/'.$this->run->slug)->assertOk();
        $page->assertSee('dates=20270304T190000/20270304T190000', false);
        $page->assertSee('dates=20270306T140000/20270306T160000', false);
        // not for the opening night, which has passed
        $this->assertSame(2, substr_count($page->getContent(), 'Add this showing to Google Calendar'));
    }

    public function test_the_page_title_gives_the_first_and_last_dates(): void
    {
        $this->assertStringEndsWith('from Mon March 1st to Sat March 6th', (string) $this->run->getDateLastTitleFormat());
        $this->assertStringEndsWith('on Fri March 5th', (string) $this->single->getDateLastTitleFormat());

        $this->get('/events/'.$this->run->slug)->assertOk()->assertSee('from Mon March 1st to Sat March 6th');

        $newYear = $this->event('ZZ New Year Run', '2026-12-28 20:00:00');
        $newYear->syncShowings([['start_at' => '2027-01-02 20:00:00']]);
        $this->assertStringEndsWith('from Mon December 28th 2026 to Sat January 2nd 2027', (string) $newYear->fresh()->getDateLastTitleFormat());
    }

    public function test_the_run_ends_with_the_latest_end_of_any_date(): void
    {
        // the opening runs all weekend, past the Saturday matinee
        $festival = Event::factory()->create(['start_at' => '2027-04-02 18:00:00', 'end_at' => '2027-04-04 23:00:00']);
        $festival->syncShowings([['start_at' => '2027-04-03 14:00:00']]);

        $this->assertSame('2027-04-04T23:00:00-04:00', EventTime::lastEndsAt($festival->fresh())?->toAtomString());
    }

    public function test_changing_only_the_showings_moves_the_events_updated_at(): void
    {
        $stamp = Carbon::parse('2027-02-01 09:00:00');
        DB::table('events')->where('id', $this->run->id)->update(['updated_at' => $stamp]);
        $run = $this->run->fresh();

        // the same showings again: nothing changed
        $run->syncShowings([
            ['start_at' => '2027-03-04 19:00:00'],
            ['start_at' => '2027-03-06 14:00:00', 'end_at' => '2027-03-06 16:00:00'],
        ]);
        $this->assertTrue($run->fresh()->updated_at->equalTo($stamp));

        // the finale moved an hour
        $run->syncShowings([
            ['start_at' => '2027-03-04 19:00:00'],
            ['start_at' => '2027-03-06 15:00:00', 'end_at' => '2027-03-06 17:00:00'],
        ]);
        $this->assertTrue($run->fresh()->updated_at->equalTo(Carbon::now()));
    }

    public function test_a_series_keeps_a_run_in_progress_in_its_json_ld(): void
    {
        $series = Series::factory()->create();
        $this->run->update(['series_id' => $series->id]);

        $document = SeriesSchema::document($series->fresh(), collect([$this->run->fresh()]));

        $this->assertSame(['ZZ Run Play'], array_column($document['subEvent'], 'name'));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function listingPages(): array
    {
        return [
            'events index' => ['/events?filters[start_at][start]=2027-03-03'],
            'events grid' => ['/events/grid?filters[start_at][start]=2027-03-03'],
            'this week' => ['/events/this-week'],
        ];
    }

    #[DataProvider('listingPages')]
    public function test_listing_pages_load_showings_with_the_events(string $url): void
    {
        foreach (['ZZ Run Two', 'ZZ Run Three'] as $name) {
            $this->event($name, '2027-03-02 20:00:00')->syncShowings([['start_at' => '2027-03-05 20:00:00']]);
        }

        $perEvent = 0;
        DB::listen(function ($query) use (&$perEvent) {
            if (str_contains($query->sql, 'from `event_showings` where `event_showings`.`event_id` = ?')) {
                $perEvent++;
            }
        });

        $this->get($url)->assertOk()->assertSee('ZZ Run Three');

        $this->assertSame(0, $perEvent);
    }
}
