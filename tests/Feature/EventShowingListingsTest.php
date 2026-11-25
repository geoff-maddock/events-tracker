<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\EventDateRange;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lists and calendars place a short-run event on every date it plays, and
 * keep it "upcoming" until its last showing (#2375).
 */
class EventShowingListingsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    /** Opened Mar 1; also plays Mar 4 and Mar 6. "Now" is Mar 3 at noon. */
    private Event $run;

    private Event $tonight;

    private Event $friday;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Carbon::setTestNow('2027-03-03 12:00:00');

        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->run = $this->event('ZZ Run Play', '2027-03-01 20:00:00');
        $this->run->syncShowings([
            ['start_at' => '2027-03-04 19:00:00'],
            ['start_at' => '2027-03-06 14:00:00', 'end_at' => '2027-03-06 16:00:00'],
        ]);
        $this->tonight = $this->event('ZZ Tonight Gig', '2027-03-03 20:00:00');
        $this->friday = $this->event('ZZ Friday Gig', '2027-03-05 20:00:00');
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
            'start_at' => $startAt,
            'end_at' => null,
            'door_at' => null,
            'cancelled_at' => null,
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * @param iterable<Event> $events
     *
     * @return array<int, string>
     */
    private function ours(iterable $events): array
    {
        return collect($events)->pluck('name')->filter(fn ($name) => str_starts_with($name, 'ZZ '))->values()->all();
    }

    public function test_a_run_in_progress_is_upcoming_and_sorts_by_its_next_showing(): void
    {
        $this->assertSame(
            ['ZZ Tonight Gig', 'ZZ Run Play', 'ZZ Friday Gig'],
            $this->ours(Event::future()->get())
        );
        $this->assertSame([], $this->ours(Event::past()->get()));

        $this->assertSame('2027-03-04 19:00', $this->run->display_start_at->format('Y-m-d H:i'));

        Carbon::setTestNow('2027-03-07 12:00:00');

        $this->assertSame([], $this->ours(Event::future()->get()));
        $this->assertContains('ZZ Run Play', $this->ours(Event::past()->get()));
        // over: back to its first date
        $this->assertSame('2027-03-01 20:00', $this->run->fresh()->display_start_at->format('Y-m-d H:i'));
    }

    public function test_a_run_occurs_on_each_of_its_dates_only(): void
    {
        $on = fn (string $day) => $this->ours(Event::query()->occurringBetween($day.' 00:00:00', $day.' 23:59:59')->get());

        $this->assertSame(['ZZ Run Play'], $on('2027-03-01'));
        $this->assertSame([], $on('2027-03-02'));
        $this->assertSame(['ZZ Run Play'], $on('2027-03-04'));
        $this->assertSame(['ZZ Run Play'], $on('2027-03-06'));
        $this->assertSame([], $on('2027-03-07'));

        Carbon::setTestNow('2027-03-04 09:00:00');
        $this->assertSame(['ZZ Run Play'], $this->ours(Event::today()->get()));
    }

    public function test_a_window_takes_what_starts_or_shows_inside_it_ends_included(): void
    {
        // the bounded form narrows by last_start_at first (#2391)
        $in = function (string $start, string $end) {
            $names = $this->ours(Event::query()->occurringBetween($start, $end)->get());
            sort($names);

            return $names;
        };

        // a window between two showings of the run, holding one single-date event
        $this->assertSame(['ZZ Tonight Gig'], $in('2027-03-02 00:00:00', '2027-03-03 23:59:59'));
        // both ends are part of the window: the run's showing at 19:00, tonight's start at 20:00
        $this->assertSame(['ZZ Run Play', 'ZZ Tonight Gig'], $in('2027-03-03 20:00:00', '2027-03-04 19:00:00'));
        $this->assertSame([], $in('2027-03-03 20:00:01', '2027-03-04 18:59:59'));
        // the run opens inside the window
        $this->assertSame(['ZZ Run Play'], $in('2027-02-20 00:00:00', '2027-03-02 00:00:00'));
        // everything, and nothing once the run is over
        $this->assertSame(['ZZ Friday Gig', 'ZZ Run Play', 'ZZ Tonight Gig'], $in('2027-03-01 00:00:00', '2027-03-31 00:00:00'));
        $this->assertSame([], $in('2027-03-06 14:00:01', '2027-03-31 00:00:00'));

        // one group, so a caller's orWhere can't escape it
        $this->assertSame(
            ['ZZ Tonight Gig'],
            $this->ours(Event::query()->where('events.name', 'like', 'ZZ %')->occurringBetween('2027-03-03 00:00:00', '2027-03-03 23:59:59')->get())
        );
        $this->assertStringContainsString(
            'where (`events`.`last_start_at` >= ? and `events`.`start_at` <= ?',
            Event::query()->occurringBetween('2027-03-03 00:00:00', '2027-03-03 23:59:59')->toSql()
        );
    }

    public function test_the_start_at_filter_matches_showings_and_sorts_by_them(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $names = fn (string $query) => $this->ours($this->getJson('/api/events?'.$query)->assertOk()->json('data'));

        $this->assertSame(
            ['ZZ Tonight Gig', 'ZZ Run Play', 'ZZ Friday Gig'],
            $names('filters[start_at][start]=2027-03-03&sort=events.start_at&direction=asc&limit=100')
        );
        $this->assertSame(
            ['ZZ Run Play'],
            $names('filters[start_at][start]=2027-03-06 00:00:00&filters[start_at][end]=2027-03-06 23:59:59&limit=100')
        );
        $this->assertSame(
            [],
            $names('filters[start_at][start]=2027-03-02 00:00:00&filters[start_at][end]=2027-03-02 23:59:59&limit=100')
        );
    }

    public function test_by_date_pages_show_the_run_on_a_showing_day(): void
    {
        $this->get('/events/by-date/2027/03/04')
            ->assertOk()
            ->assertSee('ZZ Run Play')
            ->assertSee('+ more dates')
            ->assertDontSee('ZZ Friday Gig');

        $this->get('/events/by-date/2027/03/02')->assertOk()->assertDontSee('ZZ Run Play');

        $this->actingAs($this->user, 'sanctum');
        $this->assertSame(
            ['ZZ Run Play'],
            $this->ours($this->getJson('/api/events/by-date/2027/03/06')->assertOk()->json('data'))
        );
    }

    public function test_upcoming_lists_show_the_next_showing(): void
    {
        $this->get('/events/grid?filters[start_at][start]=2027-03-03')
            ->assertOk()
            ->assertSeeInOrder(['ZZ Tonight Gig', 'ZZ Run Play', 'ZZ Friday Gig']);
    }

    public function test_the_calendar_feed_has_an_item_per_showing(): void
    {
        $items = collect($this->getJson('/calendar?start=2027-03-02&end=2027-03-08')->assertOk()->json())
            ->where('title', 'ZZ Run Play');

        $this->assertSame(['2027-03-04 19:00', '2027-03-06 14:00'], $items->pluck('start')->sort()->values()->all());
        $this->assertSame('2027-03-06 16:00', $items->firstWhere('start', '2027-03-06 14:00')['end']);
        $this->assertCount(2, $items->pluck('id')->unique());
    }

    public function test_day_views_place_the_run_under_each_day(): void
    {
        $events = Event::query()->whereIn('id', [$this->run->id, $this->tonight->id, $this->friday->id])->get();

        $days = Event::groupByDay($events, Carbon::parse('2027-03-03')->startOfDay(), Carbon::parse('2027-03-06')->endOfDay());

        $this->assertSame(['2027-03-03', '2027-03-04', '2027-03-05', '2027-03-06'], $days->keys()->sort()->values()->all());
        $this->assertSame('14:00', $days['2027-03-06'][0]->display_start_at->format('H:i'));
        $this->assertSame('19:00', $days['2027-03-04'][0]->display_start_at->format('H:i'));
        // the copies are for display: the event itself is untouched
        $this->assertSame('2027-03-01 20:00', $days['2027-03-04'][0]->start_at->format('Y-m-d H:i'));

        $this->get('/events/week')->assertOk()->assertSee('ZZ Run Play');
    }

    public function test_an_entity_keeps_a_run_in_its_upcoming_events(): void
    {
        $entity = Entity::factory()->create();
        $this->run->entities()->attach($entity->id);
        $this->friday->entities()->attach($entity->id);

        $this->assertSame(['ZZ Run Play', 'ZZ Friday Gig'], $this->ours($entity->futureEvents(10)->items()));
        $this->assertSame([], $this->ours($entity->pastEvents(10)->items()));

        $this->get('/entities/'.$entity->slug)->assertOk()->assertSee('ZZ Run Play');
    }

    public function test_the_date_range_reaches_the_last_showing(): void
    {
        EventDateRange::forget();
        $this->event('ZZ Far Run', '2030-01-01 20:00:00')->syncShowings([['start_at' => '2030-01-09 20:00:00']]);

        $this->assertTrue(app(EventDateRange::class)->contains(Carbon::parse('2030-01-09')));
    }
}
