<?php

namespace Tests\Feature\Web;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Series;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_index_loads(): void
    {
        $this->get('/calendar')->assertOk();
    }

    public function test_index_eager_loads_series_upcoming_event_without_n_plus_one(): void
    {
        // Several active series, each with a future event, so Series::nextEvent()
        // is called once per series while building the calendar event list.
        for ($i = 1; $i <= 3; $i++) {
            $series = Series::factory()->create([
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            ]);
            Event::factory()->create([
                'series_id' => $series->id,
                'start_at' => now()->addDays($i),
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            ]);
        }

        DB::enableQueryLog();
        $this->getJson($this->feedUrl())->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query');

        // The per-series "next event" lookup must be eager-loaded, not run once per series.
        $perSeriesNextEventQueries = $queries->filter(fn ($q) => str_contains($q, 'from `events`')
            && str_contains($q, 'series_id'));

        $this->assertLessThanOrEqual(
            1,
            $perSeriesNextEventQueries->count(),
            'Series next-event lookups should be eager-loaded, not queried per series (N+1).'
        );
    }

    private function feedUrl(string $query = '', int $daysBack = 1, int $daysAhead = 30): string
    {
        $start = now()->subDays($daysBack)->startOfDay()->toIso8601String();
        $end = now()->addDays($daysAhead)->endOfDay()->toIso8601String();

        return '/calendar?'.ltrim($query.'&', '&').'start='.urlencode($start).'&end='.urlencode($end);
    }

    private function publicEvent(array $attributes = []): Event
    {
        return Event::factory()->create(array_merge([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'event_type_id' => EventType::query()->value('id'),
        ], $attributes));
    }

    public function test_calendar_page_does_not_inline_events(): void
    {
        $event = $this->publicEvent(['name' => 'ZZ Inline Check Event', 'start_at' => now()->addDays(2)]);

        $response = $this->get('/calendar?filters[name]=ZZ')->assertOk();

        $response->assertDontSee($event->name);
        // the feed carries the page's filters; FullCalendar appends start/end
        $response->assertSee('filters%5Bname%5D=ZZ', false);
    }

    public function test_feed_returns_only_events_in_the_requested_range(): void
    {
        $inRange = $this->publicEvent(['name' => 'ZZ In Range', 'start_at' => now()->addDays(3)]);
        $outOfRange = $this->publicEvent(['name' => 'ZZ Far Future', 'start_at' => now()->addDays(200)]);

        $ids = collect($this->getJson($this->feedUrl())->assertOk()->json())->pluck('id');

        $this->assertTrue($ids->contains('event-'.$inRange->id));
        $this->assertFalse($ids->contains('event-'.$outOfRange->id));
    }

    public function test_feed_applies_the_page_filters(): void
    {
        $match = $this->publicEvent(['name' => 'ZZ Techno Night', 'start_at' => now()->addDays(3)]);
        $other = $this->publicEvent(['name' => 'ZZ Folk Evening', 'start_at' => now()->addDays(3)]);

        $ids = collect($this->getJson($this->feedUrl('filters[name]=Techno'))->assertOk()->json())->pluck('id');

        $this->assertTrue($ids->contains('event-'.$match->id));
        $this->assertFalse($ids->contains('event-'.$other->id));
    }

    public function test_feed_range_is_capped(): void
    {
        $late = $this->publicEvent(['start_at' => now()->addDays(150)]);

        // asks for a year; the cap stops well before day 150
        $ids = collect($this->getJson($this->feedUrl('', 1, 365))->assertOk()->json())->pluck('id');

        $this->assertFalse($ids->contains('event-'.$late->id));
    }

    public function test_api_calendar_feeds_validate_dates_and_return_events(): void
    {
        $event = $this->publicEvent(['start_at' => now()->addDays(2)]);
        $start = urlencode(now()->subDay()->toIso8601String());
        $end = urlencode(now()->addDays(10)->toIso8601String());

        $ids = collect($this->getJson("/api/calendar-events?start={$start}&end={$end}")->assertOk()->json())->pluck('id');
        $this->assertTrue($ids->contains('event-'.$event->id));

        $this->getJson("/api/tag-calendar-events?start={$start}&end={$end}")->assertOk();

        // garbage dates fall back to the current month instead of reaching the query
        $this->getJson('/api/calendar-events?start=not-a-date&end=1)%20OR%201=1')->assertOk();
    }

    public function test_api_calendar_feed_query_count_does_not_grow_with_series(): void
    {
        $start = urlencode(now()->subDay()->toIso8601String());
        $end = urlencode(now()->addDays(10)->toIso8601String());
        $url = "/api/tag-calendar-events?start={$start}&end={$end}";

        Series::factory()->count(2)->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson($url)->assertOk();
        $few = count(DB::getQueryLog());

        Series::factory()->count(8)->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        DB::flushQueryLog();
        $this->getJson($url)->assertOk();
        $many = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($few + 2, $many, "queries: 2 series = {$few}, 10 series = {$many}");
    }
}
