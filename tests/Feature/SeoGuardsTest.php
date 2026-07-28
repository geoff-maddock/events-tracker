<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SeoGuardsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withExceptionHandling();

        // the date-range clamp caches MIN/MAX(start_at); keep runs isolated
        Cache::forget('event-date-range');
    }

    protected function createEventOn(string $date): Event
    {
        return Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => $date . ' 20:00:00',
            'created_by' => User::factory()->create()->id,
        ]);
    }

    public function test_upcoming_with_date_outside_event_range_returns_404(): void
    {
        $this->createEventOn(now()->format('Y-m-d'));

        $this->get('/events/upcoming/20990101')->assertNotFound();
        $this->get('/events/upcoming/19900101')->assertNotFound();
    }

    public function test_upcoming_with_date_inside_event_range_returns_200(): void
    {
        $this->createEventOn(now()->subDay()->format('Y-m-d'));
        $this->createEventOn(now()->addDays(10)->format('Y-m-d'));

        $this->get('/events/upcoming/' . now()->format('Ymd'))->assertOk();
    }

    public function test_dated_upcoming_page_is_noindexed_and_canonicalized(): void
    {
        $this->createEventOn(now()->format('Y-m-d'));

        $response = $this->get('/events/upcoming/' . now()->format('Ymd'));

        $response->assertOk();
        $response->assertSee('noindex, follow', false);
        $response->assertSee('<link rel="canonical" href="' . url('/events/upcoming') . '">', false);
    }

    public function test_undated_upcoming_page_is_not_noindexed(): void
    {
        $this->createEventOn(now()->format('Y-m-d'));

        $this->get('/events/upcoming')->assertOk()->assertDontSee('noindex', false);
    }

    public function test_add_with_date_outside_event_range_returns_404(): void
    {
        $this->createEventOn(now()->format('Y-m-d'));

        $this->get('/events/add/20990101')->assertNotFound();
    }

    public function test_upcoming_with_unparseable_date_returns_404(): void
    {
        $this->get('/events/upcoming/www.example.com')->assertNotFound();
    }

    public function test_starting_with_junk_date_returns_404(): void
    {
        $this->get('/events/starting/junk')->assertNotFound();
        $this->get('/events/starting/2026-99-99')->assertNotFound();
    }

    public function test_starting_with_valid_date_returns_200(): void
    {
        $this->createEventOn(now()->format('Y-m-d'));

        $this->get('/events/starting/' . now()->format('Y-m-d'))->assertOk();
    }

    public function test_day_route_rejects_invalid_dates(): void
    {
        $this->get('/events/day/2026-19-99')->assertNotFound();
    }

    public function test_day_route_accepts_valid_date(): void
    {
        $this->get('/events/day/' . now()->format('Y-m-d'))->assertOk();
    }

    public function test_window_route_is_removed(): void
    {
        $this->get('/events/window/2026')->assertNotFound();
    }

    public function test_go_route_with_missing_event_returns_404(): void
    {
        $this->get('/go/evt-999999')->assertNotFound();
        $this->get('/go/ser-999999')->assertNotFound();
    }

    public function test_search_page_is_noindexed(): void
    {
        $this->get('/search?keyword=test')->assertOk()->assertSee('noindex, follow', false);
    }

    public function test_robots_txt_blocks_crawl_waste_paths(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        foreach ([
            'Disallow: /search',
            'Disallow: /go/',
            'Disallow: /users/',
            'Disallow: /events/add/',
            'Disallow: /events/grid',
            'Disallow: /*/rpp-reset',
            // d4423d36 broadened the param rules from /*?sort= so they also
            // match sort= appearing after & — keep the test in sync
            'Disallow: /*sort=',
            'Disallow: /*direction=',
            'Disallow: /*limit=',
            'Disallow: /*filters',
        ] as $line) {
            $this->assertStringContainsString($line, $robots);
        }

        $this->assertStringContainsString('Sitemap:', $robots);
    }
}
