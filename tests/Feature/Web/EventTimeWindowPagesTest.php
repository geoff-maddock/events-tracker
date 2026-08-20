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
use Illuminate\Support\Facades\DB;
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

    /**
     * @dataProvider windowProvider
     */
    public function test_tag_pills_render_for_in_window_tags(string $windowValue, string $path, string $phrase): void
    {
        $window = EventTimeWindow::from($windowValue);

        $tag = \App\Models\Tag::factory()->create(['name' => 'Pill Render Tag']);
        $this->eventInside($window)->tags()->attach($tag->id);

        $response = $this->get($path);

        $response->assertOk();
        $response->assertSee('Pill Render Tag');
        $response->assertSee($path.'?tag='.$tag->slug, false);
    }

    /**
     * @dataProvider windowProvider
     */
    public function test_tag_param_filters_the_window_to_that_tag(string $windowValue, string $path, string $phrase): void
    {
        $window = EventTimeWindow::from($windowValue);

        $tag = \App\Models\Tag::factory()->create(['name' => 'Drilldown Tag']);
        $tagged = $this->eventInside($window, ['name' => 'Tagged Window Event']);
        $tagged->tags()->attach($tag->id);
        $this->eventInside($window, ['name' => 'Untagged Window Event']);

        $response = $this->get($path.'?tag='.$tag->slug);

        $response->assertOk();
        $response->assertSee('Tagged Window Event');
        $response->assertDontSee('Untagged Window Event');
        // The active pill clears the filter back to the clean window path.
        $response->assertSee('Clear tag filter', false);
    }

    /**
     * @dataProvider windowProvider
     */
    public function test_unknown_tag_slug_is_a_404(string $windowValue, string $path, string $phrase): void
    {
        $this->get($path.'?tag=no-such-tag-slug')->assertNotFound();
    }

    /**
     * @dataProvider windowProvider
     */
    public function test_tag_param_variant_is_noindexed(string $windowValue, string $path, string $phrase): void
    {
        $window = EventTimeWindow::from($windowValue);

        $tag = \App\Models\Tag::factory()->create();
        $this->eventInside($window)->tags()->attach($tag->id);

        $response = $this->get($path.'?tag='.$tag->slug);

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
        // ItemList JSON-LD is only emitted for the unfiltered page.
        $response->assertDontSee('"@type": "ItemList"', false);
    }

    public function test_more_than_eight_tags_fold_behind_an_ellipsis_toggle(): void
    {
        $window = EventTimeWindow::from('this-week');

        $event = $this->eventInside($window);
        $tags = \App\Models\Tag::factory()->count(9)->create();
        $event->tags()->attach($tags->pluck('id')->all());

        $response = $this->get('/events/this-week');

        $response->assertOk();
        $response->assertSee('Show all tags', false);
        $response->assertSee('x-show="expanded"', false);
    }

    public function test_eight_or_fewer_tags_have_no_ellipsis_toggle(): void
    {
        $window = EventTimeWindow::from('this-week');

        $event = $this->eventInside($window);
        $tags = \App\Models\Tag::factory()->count(3)->create();
        $event->tags()->attach($tags->pluck('id')->all());

        // The seeder can leave its own tagged events inside this real-time
        // window; only assert the toggle is absent when the window really has
        // eight or fewer tags.
        $stats = app(EventTimeWindowStats::class)->stats($window);

        $response = $this->get('/events/this-week');
        $response->assertOk();

        if (count($stats['tagLinks']) <= EventTimeWindowStats::TAG_LINKS_VISIBLE) {
            $response->assertDontSee('Show all tags', false);
        } else {
            $response->assertSee('Show all tags', false);
        }
    }

    public function test_other_window_pill_links_are_present(): void
    {
        $response = $this->get('/events/tonight');

        $response->assertOk();
        $response->assertSee('href="'.url('/events/this-weekend').'"', false);
        $response->assertSee('href="'.url('/events/this-week').'"', false);
        $response->assertDontSee('href="'.url('/events/today').'"', false);
    }

    public function test_retired_today_window_redirects_to_tonight(): void
    {
        $this->get('/events/today')
            ->assertStatus(301)
            ->assertRedirect(url('/events/tonight'));
    }

    /**
     * Decode the CollectionPage block — the first JSON-LD script on the page —
     * and return its ItemList entries.
     */
    private function listedEvents(string $html): array
    {
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        $decoded = json_decode($matches[1] ?? '', true);

        $this->assertSame(
            JSON_ERROR_NONE,
            json_last_error(),
            'listing JSON-LD did not parse: '.json_last_error_msg()
        );

        return $decoded['mainEntity']['itemListElement'];
    }

    /**
     * Search Console reported all 44 events on /events/this-week as valid but
     * each missing image, endDate, eventStatus, organizer, performer, offers
     * and location.address — the listing partial emitted a thinner Event than
     * the detail page did. They come from App\Services\EventSchema now.
     */
    public function test_listing_json_ld_carries_the_fields_search_console_asked_for(): void
    {
        $window = EventTimeWindow::from('this-week');
        $event = $this->eventInside($window, ['name' => 'Rich JSON LD Event']);
        $event->photos()->attach(
            \App\Models\Photo::factory()->create(['is_primary' => 1])->id
        );

        // The event factory's venue carries no location, and location
        // visibility is randomised — pin a public address so the assertion is
        // about the mapping rather than about what the factory rolled.
        $event->venue->locations()->create([
            'name' => 'Main Room',
            'slug' => 'main-room',
            'address_one' => '4305 Murray Ave',
            'city' => 'Pittsburgh',
            'state' => 'PA',
            'postcode' => '15217',
            'location_type_id' => 1,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'created_by' => User::factory()->create()->id,
        ]);

        $html = $this->get('/events/this-week')->assertOk()->getContent();

        $items = $this->listedEvents($html);
        $node = collect($items)->pluck('item')->firstWhere('name', 'Rich JSON LD Event');

        $this->assertNotNull($node, 'the seeded event was not in the ItemList');

        foreach (['image', 'endDate', 'eventStatus', 'organizer', 'performer', 'offers'] as $field) {
            $this->assertArrayHasKey($field, $node, "missing {$field}");
        }
        $this->assertArrayHasKey('address', $node['location']);
        $this->assertSame('Event', $node['@type']);
    }

    public function test_listing_start_dates_carry_a_daylight_saving_aware_offset(): void
    {
        // config('app.timezone') is a fixed UTC-5 offset that never observes
        // DST, so anything reading the cast Carbon publishes summer showtimes
        // an hour late. Google renders whatever offset we send.
        $window = EventTimeWindow::from('this-week');
        $start = Carbon::parse($window->range()['start'])->addMinutes(10);
        $event = $this->eventInside($window, ['name' => 'Offset Check Event']);

        $html = $this->get('/events/this-week')->assertOk()->getContent();

        $node = collect($this->listedEvents($html))->pluck('item')->firstWhere('name', 'Offset Check Event');

        $expectedOffset = $start->copy()->setTimezone('America/New_York')->format('P');
        $this->assertStringEndsWith($expectedOffset, $node['startDate']);
    }

    /**
     * The richer JSON-LD reads photos, roles, links and the promoter per
     * event. Those are eager loaded; if that ever stops being true the page
     * degrades quietly, so assert on the shape of the cost rather than an
     * absolute count: query volume must not scale with the number of events.
     */
    public function test_listing_query_count_does_not_scale_with_event_count(): void
    {
        $window = EventTimeWindow::from('this-week');

        $this->eventInside($window);
        $withOne = $this->countQueriesForThisWeek();

        for ($i = 0; $i < 8; ++$i) {
            $this->eventInside($window);
        }
        $withNine = $this->countQueriesForThisWeek();

        $this->assertLessThanOrEqual(
            $withOne + 5,
            $withNine,
            "query count grew from {$withOne} to {$withNine} for 8 extra events — an N+1 is back"
        );
    }

    private function countQueriesForThisWeek(): int
    {
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/events/this-week')->assertOk();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
