<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventResponse;
use App\Models\ResponseType;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The event feeds and exports that read the main list's saved filters, and
 * the links that save or clear them (#2406).
 */
class EventFeedListsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const FILTERS = 'internal_event_index/index/filters';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        Event::query()->delete();
        foreach (['Zzpast show' => -3, 'Zzsoon show' => 2, 'Zzlater gig' => 5] as $name => $days) {
            Event::factory()->create([
                'name' => $name, 'visibility_id' => Visibility::VISIBILITY_PUBLIC,
                'start_at' => now()->addDays($days)->setTime(20, 0), 'end_at' => null,
            ]);
        }
    }

    public function test_the_feeds_follow_the_filter_saved_for_the_event_list(): void
    {
        // from today until a filter says otherwise (#2391)
        $this->get('/events/feed')->assertOk()->assertSeeInOrder(['Zzsoon show', 'Zzlater gig'])->assertDontSee('Zzpast show');
        $this->get('/events/brief-text')->assertOk()->assertSeeInOrder(['Zzsoon show', 'Zzlater gig'])->assertDontSee('Zzpast show');

        $this->post('/events/filter', ['filters' => ['name' => 'show']])->assertOk();

        $this->get('/events/feed')->assertOk()->assertSee('Zzsoon show')->assertSee('Zzpast show')->assertDontSee('Zzlater gig');
        $this->get('/events/brief-text')->assertOk()->assertSee('Zzsoon show')->assertDontSee('Zzlater gig');
        $this->get('/events/export')->assertOk()->assertSee('Zzsoon show')->assertDontSee('Zzlater gig');

        $this->get('/events/reset')->assertRedirect(route('events.index'));
        $this->get('/events/feed')->assertOk()->assertSee('Zzlater gig');
    }

    public function test_the_ical_feed_starts_today(): void
    {
        $this->get('/events/ical')->assertOk()
            ->assertSee('Zzsoon show')->assertSee('Zzlater gig')->assertDontSee('Zzpast show');
    }

    public function test_a_feed_ignores_a_sort_column_that_is_not_offered(): void
    {
        $this->get('/events/feed?sort=no_such_column')->assertOk()->assertSee('Zzsoon show');
    }

    public function test_a_feed_sorts_only_by_the_columns_the_event_list_offers(): void
    {
        // added in the reverse of their date order
        Event::where('name', 'Zzlater gig')->update(['created_at' => now()->subDays(9)]);
        Event::where('name', 'Zzpast show')->update(['created_at' => now()->subDays(1)]);

        // a real column, but not a sort the list offers: the default column, in the direction asked for
        $all = '&filters[start_at][start]='.now()->subDays(30)->format('Y-m-d');

        $this->get('/events/feed?sort=events.created_at&direction=asc'.$all)->assertOk()
            ->assertSeeInOrder(['Zzpast show', 'Zzsoon show', 'Zzlater gig']);
        $this->get('/events/feed?sort=events.name&direction=asc'.$all)->assertOk()
            ->assertSeeInOrder(['Zzlater gig', 'Zzpast show', 'Zzsoon show']);
    }

    public function test_the_feed_comes_a_hundred_events_at_a_time(): void
    {
        // it was 1,000 events of every date on one page: 5 MB (#2391)
        $venue = Event::where('name', 'Zzsoon show')->firstOrFail()->venue_id;
        Event::factory()->count(100)->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'venue_id' => $venue,
            'start_at' => now()->addDays(3)->setTime(20, 0), 'end_at' => null,
        ]);

        $first = $this->get('/events/feed')->assertOk()->assertSee('Zzsoon show')->assertDontSee('Zzlater gig')
            ->assertSee('rel="next"', false)->assertDontSee('rel="prev"', false);
        $this->assertCount(100, $first->viewData('events'));

        $this->get('/events/feed?page=2')->assertOk()->assertSee('Zzlater gig')->assertDontSee('Zzsoon show')
            ->assertSee('rel="prev"', false)->assertDontSee('rel="next"', false);
    }

    public function test_the_feeds_leave_out_what_the_viewer_may_not_see(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $private = Event::factory()->create([
            'name' => 'Zzhidden party', 'visibility_id' => Visibility::VISIBILITY_PRIVATE, 'created_by' => $owner->id,
            'start_at' => now()->addDays(3)->setTime(20, 0), 'end_at' => null,
        ]);
        $public = Event::where('name', 'Zzsoon show')->firstOrFail();
        foreach ([$private, $public] as $event) {
            EventResponse::create([
                'event_id' => $event->id, 'user_id' => $member->id, 'response_type_id' => ResponseType::ATTENDING,
            ]);
        }

        foreach (['/events/feed', '/events/brief-text', '/events/export', '/events/ical'] as $url) {
            $this->get($url)->assertOk()->assertSee('Zzsoon show')->assertDontSee('Zzhidden party');
        }

        // the member said they are going to both, and still only gets the one they may see
        $this->actingAs($member);
        $this->get('/events/feed')->assertOk()->assertDontSee('Zzhidden party');
        foreach (['/events/export-attending', "/users/{$member->id}/attending-ical"] as $url) {
            $this->get($url)->assertOk()->assertSee('Zzsoon show')->assertDontSee('Zzhidden party')->assertDontSee('Zzlater gig');
        }

        $this->actingAs($owner);
        $this->get('/events/feed')->assertOk()->assertSee('Zzhidden party');
    }

    public function test_a_shared_link_saves_its_filters_for_the_list_it_names(): void
    {
        $this->get('/events/apply-filter?filters[name]=gig&sort=events.name&direction=asc&limit=10')
            ->assertRedirect(route('events.filter'));
        $this->assertSame(['name' => 'gig'], session(self::FILTERS));
        $this->get('/events/filter')->assertOk()->assertSee('Zzlater gig')->assertDontSee('Zzsoon show');

        // without filters nothing is saved or cleared
        $this->get('/events/apply-filter')->assertRedirect(route('events.filter'));
        $this->assertSame(['name' => 'gig'], session(self::FILTERS));

        $this->get('/events/grid/apply-filter?filters[name]=show')->assertRedirect(route('events.grid'));
        $this->assertSame(['name' => 'show'], session('internal_event_grid/index/filters'));
        $this->assertSame(['name' => 'gig'], session(self::FILTERS));
    }

    public function test_the_days_pages_reject_what_is_not_a_date_with_events(): void
    {
        $this->get('/events/upcoming')->assertOk();
        $this->get('/events/upcoming/'.now()->addDay()->format('Y-m-d'))->assertOk();
        $this->get('/events/upcoming/not-a-date')->assertNotFound();
        $this->get('/events/upcoming/1990-01-01')->assertNotFound();
        $this->get('/events/add/'.now()->addDay()->format('Ymd'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
    }

    public function test_the_attending_list_resets_go_back_to_that_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get("/users/{$user->id}/attending-ical")->assertOk();
        $this->post("/users/{$user->id}/reset-user-attending")->assertRedirect(route('users.attending', ['id' => $user->id]));
        $this->get("/users/{$user->id}/rpp-reset-user-attending")->assertRedirect(route('users.attending', ['id' => $user->id]));
    }
}
