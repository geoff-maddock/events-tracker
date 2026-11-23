<?php

namespace Tests\Feature\Web;

use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Follow;
use App\Models\ResponseType;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Home (4-day) and /events/week (#2168): series next dates are computed once per
 * request, event lists are loaded in bulk, and cards don't query per item.
 */
class HomeAndWeekPagesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const WEEKLY = 2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * Noon (app timezone) on the date the home page treats as today. The home page and
     * series cycle use America/New_York while the app timezone is a fixed EST, so for an
     * hour each night "today" differs between them; noon is the same date in both.
     */
    private function homeToday(): Carbon
    {
        return Carbon::parse(Carbon::now('America/New_York')->format('Y-m-d').' 12:00:00');
    }

    private function weeklySeriesToday(array $attributes = []): Series
    {
        // founded on the home page's today, so its next occurrence is that day
        return Series::factory()->create(array_merge([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'occurrence_type_id' => self::WEEKLY,
            'founded_at' => $this->homeToday(),
            'cancelled_at' => null,
        ], $attributes));
    }

    private function seriesQueryCount(callable $request): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request();
        $count = collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_starts_with($q, 'select * from `series`'))->count();
        DB::disableQueryLog();

        return $count;
    }

    public function test_home_page_loads_series_once_for_all_four_days(): void
    {
        $this->weeklySeriesToday(['name' => 'ZZ Weekly Series']);

        $count = $this->seriesQueryCount(fn () => $this->get('/')->assertOk()->assertSee('ZZ Weekly Series'));

        $this->assertSame(1, $count, 'the series list should be loaded once, not once per day');
    }

    public function test_every_day_view_lists_a_series_due_that_day_unless_its_event_is_listed(): void
    {
        $this->weeklySeriesToday(['name' => 'ZZ Weekly Series']);
        $withEvent = $this->weeklySeriesToday(['name' => 'ZZ Listed Series']);
        Event::factory()->create([
            'name' => 'ZZ Listed Night', 'series_id' => $withEvent->id, 'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => $this->homeToday()->setTime(21, 0), 'end_at' => null,
        ]);
        $today = $this->homeToday()->format('Y-m-d');
        $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

        foreach ([['/', []], ['/', $ajax], ["/events/upcoming/$today", []], ["/events/upcoming/$today", $ajax], ["/events/day/$today", []]] as [$url, $headers]) {
            $this->get($url, $headers)->assertOk()
                ->assertSee('ZZ Weekly Series')->assertSee('ZZ Listed Night')
                // that series is shown by its event, not again under "Series"
                ->assertDontSee('ZZ Listed Series');
        }
    }

    public function test_cancelled_series_are_only_listed_before_their_cancellation_date(): void
    {
        $today = $this->homeToday();
        $this->weeklySeriesToday([
            'name' => 'ZZ Series Cancelled After Its Occurrence',
            'cancelled_at' => $today->copy()->addDay()->format('Y-m-d H:i:s'),
        ]);
        $this->weeklySeriesToday(['name' => 'ZZ Series Cancelled Today', 'cancelled_at' => $today->copy()]);
        $this->weeklySeriesToday(['name' => 'ZZ Series Cancelled Yesterday', 'cancelled_at' => $today->copy()->subDay()]);

        foreach (['/', '/events/upcoming/'.$today->format('Y-m-d'), '/events/day/'.$today->format('Y-m-d'), route('events.week')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('ZZ Series Cancelled After Its Occurrence')
                ->assertDontSee('ZZ Series Cancelled Today')
                ->assertDontSee('ZZ Series Cancelled Yesterday');
        }
    }

    public function test_week_page_loads_series_once_and_events_in_one_query(): void
    {
        $this->weeklySeriesToday(['name' => 'ZZ Weekly Series']);
        $soon = Event::factory()->create(['name' => 'ZZ Tomorrow Show', 'start_at' => now()->addDay()->setTime(21, 0), 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $later = Event::factory()->create(['name' => 'ZZ Next Month Show', 'start_at' => now()->addDays(30), 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->get(route('events.week'))->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $response->assertSee('ZZ Tomorrow Show')->assertSee($soon->venue->name)->assertDontSee('ZZ Next Month Show');
        $response->assertSee('ZZ Weekly Series');
        $this->assertSame(1, $queries->filter(fn ($q) => str_starts_with($q, 'select * from `series`'))->count());
        // (the series' upcomingEvent eager load also reads events, by series_id)
        $weekEventQueries = $queries->filter(fn ($q) => str_starts_with($q, 'select * from `events`') && !str_contains($q, '`series_id` in'));
        $this->assertSame(1, $weekEventQueries->count(), 'events for the week should be one query');
    }

    public function test_week_page_hides_other_users_private_events(): void
    {
        Event::factory()->create(['name' => 'ZZ Secret Show', 'start_at' => now()->addDay()->setTime(21, 0), 'visibility_id' => Visibility::VISIBILITY_PRIVATE]);

        $this->get(route('events.week'))->assertOk()->assertDontSee('ZZ Secret Show');
    }

    public function test_series_card_follow_state_uses_the_viewers_follows(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $followed = $this->weeklySeriesToday(['name' => 'ZZ Followed Series']);
        $this->weeklySeriesToday(['name' => 'ZZ Other Series']);
        Follow::create(['user_id' => $user->id, 'object_type' => 'series', 'object_id' => $followed->id]);

        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(route('series.unfollow', ['id' => $followed->id]), $html);
        $this->assertSame(1, substr_count($html, '/unfollow'), 'only the followed series shows an unfollow link');
    }

    public function test_home_shows_the_viewers_own_attend_state(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $event = Event::factory()->create(['start_at' => $this->homeToday(), 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        EventResponse::create(['event_id' => $event->id, 'user_id' => $other->id, 'response_type_id' => ResponseType::ATTENDING]);

        // someone else attending must not show as the viewer attending
        $this->actingAs($user)->get('/')->assertOk()->assertDontSee(route('events.unattend', ['id' => $event->id]));

        EventResponse::create(['event_id' => $event->id, 'user_id' => $user->id, 'response_type_id' => ResponseType::ATTENDING]);
        $this->actingAs($user)->get('/')->assertOk()->assertSee(route('events.unattend', ['id' => $event->id]));
    }

    public function test_today_scope_uses_a_date_range(): void
    {
        $today = Event::factory()->create(['start_at' => now()->startOfDay()->addHours(20)]);
        $tomorrow = Event::factory()->create(['start_at' => now()->addDay()->startOfDay()->addHour()]);

        $ids = Event::today()->pluck('id');

        $this->assertTrue($ids->contains($today->id));
        $this->assertFalse($ids->contains($tomorrow->id));
        $this->assertStringNotContainsString('date(', strtolower(Event::today()->toSql()));
    }

    public function test_an_event_card_offers_tickets_once(): void
    {
        Event::factory()->create([
            'name' => 'ZZ Ticketed Show', 'start_at' => now()->addDays(2),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'ticket_link' => 'https://tickets.example.test/zz',
        ]);

        // the grid card of the events list (the day views use a different card)
        $html = $this->get('/events')->assertOk()->assertSee('ZZ Ticketed Show')->getContent();

        $this->assertSame(1, substr_count($html, 'Buy Tickets</span>'));
    }
}
