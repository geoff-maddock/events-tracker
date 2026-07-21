<?php

namespace Tests\Feature\Web;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventsControllerSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        // Most events routes are public, but the create/edit/duplicate
        // routes need an active user.
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]));
    }

    public function test_events_index_renders(): void
    {
        Event::factory()->count(2)->create();

        $this->get('/events')->assertOk();
    }

    public function test_events_show_renders_for_existing_event(): void
    {
        $event = Event::factory()->create();

        $this->get('/events/'.$event->id)->assertOk();
    }

    public function test_events_create_renders(): void
    {
        $this->get('/events/create')->assertOk();
    }

    public function test_events_grid_renders(): void
    {
        Event::factory()->count(2)->create();

        $this->get('/events/grid')->assertOk();
    }

    public function test_events_future_renders(): void
    {
        Event::factory()->create(['start_at' => Carbon::now()->addDays(7)]);

        $this->get('/events/future')->assertOk();
    }

    public function test_events_past_renders(): void
    {
        Event::factory()->create(['start_at' => Carbon::now()->subDays(7)]);

        $this->get('/events/past')->assertOk();
    }

    public function test_events_today_renders(): void
    {
        Event::factory()->create(['start_at' => Carbon::now()->setTime(20, 0)]);

        $this->get('/events/today')->assertOk();
    }

    public function test_events_day_by_string_renders(): void
    {
        $this->get('/events/day/'.Carbon::now()->format('Y-m-d'))->assertOk();
    }

    public function test_events_by_date_year_only(): void
    {
        $year = Carbon::now()->year;

        $this->get('/events/by-date/'.$year)->assertOk();
    }

    public function test_events_by_date_single_digit_month_renders(): void
    {
        // A single-digit month previously built the unparseable string "2026801"
        // and threw a Carbon InvalidFormatException (EVENTREPO-WG).
        $this->get('/events/by-date/2026/8')->assertOk();
    }

    public function test_events_by_date_single_digit_month_and_day_renders(): void
    {
        $this->get('/events/by-date/2026/8/5')->assertOk();
    }

    public function test_events_week_renders(): void
    {
        Event::factory()->create(['start_at' => Carbon::now()->setTime(20, 0)]);

        $this->get('/events/week')->assertOk();
    }

    public function test_events_grid_tag_renders(): void
    {
        $tag = Tag::factory()->create(['slug' => 'zz-grid-tag']);
        $event = Event::factory()->create();
        $event->tags()->attach($tag->id);

        $this->get('/events/grid/tag/zz-grid-tag')->assertOk();
    }

    public function test_events_grid_type_renders(): void
    {
        $type = EventType::first();

        $this->get('/events/grid/type/'.$type->slug)->assertOk();
    }

    public function test_events_grid_series_renders(): void
    {
        $series = Series::factory()->create(['slug' => 'zz-series-grid']);

        $this->get('/events/grid/series/zz-series-grid')->assertOk();
    }

    public function test_events_grid_related_to_renders(): void
    {
        $entity = Entity::factory()->create(['slug' => 'zz-related-grid']);
        $event = Event::factory()->create();
        $event->entities()->attach($entity->id);

        $this->get('/events/grid/related-to/zz-related-grid')->assertOk();
    }

    public function test_events_reset_redirects(): void
    {
        $this->get('/events/reset')->assertRedirect('/events');
    }
}
