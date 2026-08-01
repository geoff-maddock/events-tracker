<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\OccurrenceDay;
use App\Models\OccurrenceType;
use App\Models\Series;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesSeoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function occurrenceType(string $name): int
    {
        return OccurrenceType::where('name', $name)->value('id');
    }

    private function nonFestivalEventType(): int
    {
        return EventType::where('slug', '!=', 'festival')->inRandomOrder()->value('id');
    }

    /**
     * A Yearly-occurrence series (no Festival event type needed — Yearly
     * alone should trip the festival title) with a future-dated event: the
     * title should include the event's year and the brand suffix.
     */
    public function test_yearly_series_title_includes_upcoming_year_and_brand(): void
    {
        $series = Series::factory()->create([
            'name' => 'Skull Fest',
            'event_type_id' => $this->nonFestivalEventType(),
            'occurrence_type_id' => $this->occurrenceType('Yearly'),
        ]);

        $upcoming = Carbon::now()->addMonths(4);
        Event::factory()->create([
            'series_id' => $series->id,
            'start_at' => $upcoming,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->assertSame($upcoming->year, $series->fresh(['occurrenceType', 'eventType'])->getFestivalYear());

        $response = $this->get('/series/'.$series->slug);

        $response->assertOk();
        $expectedTitle = e('Skull Fest '.$upcoming->year.' — Lineup, Schedule & Tickets in Pittsburgh').' | '.config('app.app_name');
        $response->assertSee('<title>'.$expectedTitle.'</title>', false);
    }

    /**
     * A Festival-eventType series (Weekly occurrence, so it's the event type
     * — not the occurrence type — driving festival detection) with only
     * past events: the title should fall back to the most recent past year.
     */
    public function test_festival_event_type_series_with_only_past_events_uses_past_year(): void
    {
        $festivalTypeId = EventType::where('slug', 'festival')->value('id');

        $series = Series::factory()->create([
            'name' => 'Millvale Music Festival',
            'event_type_id' => $festivalTypeId,
            'occurrence_type_id' => $this->occurrenceType('Weekly'),
        ]);

        $past = Carbon::now()->subYear();
        Event::factory()->create([
            'series_id' => $series->id,
            'start_at' => $past,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $response = $this->get('/series/'.$series->slug);

        $response->assertOk();
        $expectedTitle = e('Millvale Music Festival '.$past->year.' — Lineup, Schedule & Tickets in Pittsburgh').' | '.config('app.app_name');
        $response->assertSee('<title>'.$expectedTitle.'</title>', false);
    }

    /**
     * A Weekly, non-festival series with no venue: no year in the title,
     * clean " — " format, and no dangling separators from the missing venue.
     */
    public function test_weekly_series_title_has_no_year_and_no_dangling_separators(): void
    {
        $series = Series::factory()->create([
            'name' => 'Weekly Jazz Night',
            'event_type_id' => $this->nonFestivalEventType(),
            'occurrence_type_id' => $this->occurrenceType('Weekly'),
            'occurrence_day_id' => OccurrenceDay::where('name', 'Friday')->value('id'),
            'venue_id' => null,
        ]);

        $title = $series->fresh(['occurrenceType', 'occurrenceDay', 'eventType', 'venue'])->getSeoTitleFormat();

        $this->assertStringContainsString('Weekly Jazz Night — Weekly Fridays', $title);
        $this->assertStringNotContainsString(' at ', $title);
        $this->assertStringNotContainsString('  ', $title);
        $this->assertNotEquals(' ', substr($title, -1));
        $this->assertNotEquals('—', substr(trim($title), -1));

        $response = $this->get('/series/'.$series->slug);

        $response->assertOk();
        $expectedTitle = e($title).' | '.config('app.app_name');
        $response->assertSee('<title>'.$expectedTitle.'</title>', false);
    }

    /**
     * With an upcoming event that has an attached entity, both the Lineup and
     * Schedule sections should render, and the schedule should list the
     * upcoming events in ascending date order.
     */
    public function test_page_shows_lineup_and_schedule_with_events_in_ascending_order(): void
    {
        $series = Series::factory()->create([
            'name' => 'Skull Fest',
            'event_type_id' => $this->nonFestivalEventType(),
            'occurrence_type_id' => $this->occurrenceType('Yearly'),
        ]);

        $performer = Entity::factory()->create(['name' => 'ZZ Lineup Performer '.uniqid()]);

        $laterEvent = Event::factory()->create([
            'name' => 'ZZ Later Night '.uniqid(),
            'series_id' => $series->id,
            'start_at' => Carbon::now()->addMonths(5),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
        $earlierEvent = Event::factory()->create([
            'name' => 'ZZ Earlier Night '.uniqid(),
            'series_id' => $series->id,
            'start_at' => Carbon::now()->addMonths(2),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
        $earlierEvent->entities()->attach($performer->id);

        $response = $this->get('/series/'.$series->slug);

        $response->assertOk();
        $response->assertSee('Lineup');
        // The Schedule heading is the series name + its nearest upcoming
        // year (the earlier event) + the literal word "Schedule" — distinct
        // from the sidebar's unrelated "Schedule Information" label.
        $response->assertSee($series->name.' '.$earlierEvent->start_at->year.' Schedule');
        $response->assertSee($performer->name);
        $response->assertSeeInOrder([$earlierEvent->name, $laterEvent->name]);
    }

    /**
     * A non-festival series (Weekly occurrence, non-festival event type)
     * with an upcoming event should still get a Schedule section, but its
     * heading must NOT carry a fabricated "edition year" — that's a
     * festival-only concept. Regression test for a review finding: the
     * controller previously passed $festivalYear unconditionally, so an
     * ordinary weekly club night rendered "{name} {year} Schedule".
     */
    public function test_non_festival_series_schedule_heading_has_no_year(): void
    {
        $series = Series::factory()->create([
            'name' => 'Weekly Jazz Night',
            'event_type_id' => $this->nonFestivalEventType(),
            'occurrence_type_id' => $this->occurrenceType('Weekly'),
            'occurrence_day_id' => OccurrenceDay::where('name', 'Friday')->value('id'),
        ]);

        $this->assertFalse($series->fresh(['occurrenceType', 'eventType'])->isFestival());

        $upcoming = Carbon::now()->addWeeks(2);
        Event::factory()->create([
            'series_id' => $series->id,
            'start_at' => $upcoming,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $response = $this->get('/series/'.$series->slug);

        $response->assertOk();
        $response->assertSee($series->name.' Schedule');
        $response->assertDontSee($series->name.' '.$upcoming->year.' Schedule');
    }

    /**
     * Unit coverage of the year-fallback chain: upcoming -> past -> none.
     */
    public function test_get_festival_year_falls_back_from_upcoming_to_past_to_none(): void
    {
        $series = Series::factory()->create([
            'event_type_id' => EventType::where('slug', 'festival')->value('id'),
            'occurrence_type_id' => $this->occurrenceType('Weekly'),
        ]);

        // No events at all -> null.
        $this->assertNull($series->fresh()->getFestivalYear());

        // Only a past event -> that event's year.
        $past = Carbon::now()->subYears(2);
        Event::factory()->create([
            'series_id' => $series->id,
            'start_at' => $past,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
        $this->assertSame($past->year, $series->fresh()->getFestivalYear());

        // A later upcoming event takes priority over the past one.
        $upcoming = Carbon::now()->addMonths(3);
        Event::factory()->create([
            'series_id' => $series->id,
            'start_at' => $upcoming,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);
        $this->assertSame($upcoming->year, $series->fresh()->getFestivalYear());
    }
}
