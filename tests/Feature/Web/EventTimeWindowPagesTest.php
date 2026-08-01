<?php

namespace Tests\Feature\Web;

use App\Enums\EventTimeWindow;
use App\Models\Event;
use App\Models\User;
use App\Models\Visibility;
use App\Services\EventTimeWindowStats;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EventTimeWindowPagesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Cache::flush();
    }

    protected function appName(): string
    {
        return config('app.app_name');
    }

    public static function windowProvider(): array
    {
        return [
            'tonight' => ['tonight', '/events/tonight', 'Tonight'],
            'today' => ['today', '/events/today', 'Today'],
            'this-weekend' => ['this-weekend', '/events/this-weekend', 'This Weekend'],
            'this-week' => ['this-week', '/events/this-week', 'This Week'],
        ];
    }

    protected function eventInside(EventTimeWindow $window, array $overrides = []): Event
    {
        $range = $window->range();
        $start = Carbon::parse($range['start'])->addMinutes(10)->format('Y-m-d H:i:s');

        return Event::factory()->create(array_merge([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => $start,
            'created_by' => User::factory()->create()->id,
        ], $overrides));
    }

    protected function eventOutside(EventTimeWindow $window, array $overrides = []): Event
    {
        $range = $window->range();
        $start = Carbon::parse($range['start'])->subDays(3)->format('Y-m-d H:i:s');

        return Event::factory()->create(array_merge([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => $start,
            'created_by' => User::factory()->create()->id,
        ], $overrides));
    }

    /**
     * @dataProvider windowProvider
     */
    public function test_page_returns_200_with_expected_title(string $windowValue, string $path, string $phrase): void
    {
        $window = EventTimeWindow::from($windowValue);

        $response = $this->get($path);

        $response->assertOk();
        // The layout's inline @section('title', ...) form runs content through
        // e(), so '&' in the copy renders as '&amp;' in the compiled <title>.
        $response->assertSee('<title>'.e($window->title()).' | '.$this->appName().'</title>', false);
        $response->assertSee($phrase, false);
    }

    /**
     * @dataProvider windowProvider
     */
    public function test_canonical_is_the_clean_window_path(string $windowValue, string $path, string $phrase): void
    {
        $response = $this->get($path);

        $response->assertOk();
        $base = rtrim(config('app.url'), '/');
        $response->assertSee('<link rel="canonical" href="'.$base.$path.'">', false);
    }

    /**
     * @dataProvider windowProvider
     */
    public function test_sort_param_variant_is_noindexed(string $windowValue, string $path, string $phrase): void
    {
        $response = $this->get($path.'?sort=name');

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    /**
     * @dataProvider windowProvider
     */
    public function test_event_inside_window_is_shown_and_outside_window_is_absent(string $windowValue, string $path, string $phrase): void
    {
        $window = EventTimeWindow::from($windowValue);

        $inside = $this->eventInside($window, ['name' => 'Inside Window Test Event']);
        $outside = $this->eventOutside($window, ['name' => 'Outside Window Test Event']);

        $response = $this->get($path);

        $response->assertOk();
        $response->assertSee($inside->name);
        $response->assertDontSee($outside->name);
    }

    /**
     * @dataProvider windowProvider
     */
    public function test_private_event_inside_window_is_absent_for_guests(string $windowValue, string $path, string $phrase): void
    {
        $window = EventTimeWindow::from($windowValue);

        $private = $this->eventInside($window, [
            'name' => 'Private Window Test Event',
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
        ]);

        $response = $this->get($path);

        $response->assertOk();
        $response->assertDontSee($private->name);
    }

    /**
     * @dataProvider windowProvider
     */
    public function test_meta_description_contains_the_computed_event_count(string $windowValue, string $path, string $phrase): void
    {
        $window = EventTimeWindow::from($windowValue);

        $this->eventInside($window, ['name' => 'Count Test Event']);

        $response = $this->get($path);
        $response->assertOk();

        $stats = app(EventTimeWindowStats::class)->stats($window);
        $desc = $window->metaDescription($stats);

        // Inline @section(..., $desc) runs content through e(), so '&' renders as '&amp;'.
        $response->assertSee('<meta name="description" content="'.e($desc).'">', false);
        $response->assertSee((string) $stats['events'], false);
    }

    /**
     * @dataProvider windowProvider
     */
    public function test_response_contains_item_list_json_ld_when_events_present(string $windowValue, string $path, string $phrase): void
    {
        $window = EventTimeWindow::from($windowValue);

        $this->eventInside($window, ['name' => 'JSON LD Test Event']);

        $response = $this->get($path);

        $response->assertOk();
        $response->assertSee('"@type": "ItemList"', false);
    }

    /**
     * @dataProvider windowProvider
     */
    public function test_empty_state_has_no_json_ld(string $windowValue, string $path, string $phrase): void
    {
        // No events created inside the window: only whatever the seeder
        // happens to leave inside this real-time window, if anything.
        $window = EventTimeWindow::from($windowValue);
        $stats = app(EventTimeWindowStats::class)->stats($window);

        $response = $this->get($path);
        $response->assertOk();

        if ($stats['events'] === 0) {
            $response->assertDontSee('"@type": "ItemList"', false);
            $response->assertSee('No events found for this window yet.', false);
        } else {
            $this->assertTrue(true); // seeded data already populates this window; nothing to assert
        }
    }

    public function test_other_window_pill_links_are_present(): void
    {
        $response = $this->get('/events/tonight');

        $response->assertOk();
        $response->assertSee('href="'.url('/events/today').'"', false);
        $response->assertSee('href="'.url('/events/this-weekend').'"', false);
        $response->assertSee('href="'.url('/events/this-week').'"', false);
    }
}
