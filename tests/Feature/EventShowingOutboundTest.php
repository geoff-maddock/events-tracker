<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Visibility;
use App\Services\Calendar\ICalBuilder;
use App\Services\EventSchema;
use App\Services\RssFeed;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertStringContainsString("UID:{$uid}#20270304T1900\r\n", $ics);
        $this->assertStringContainsString("UID:{$uid}#20270306T1400\r\n", $ics);
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
    }
}
