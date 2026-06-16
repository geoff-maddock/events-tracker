<?php

namespace Tests\Feature\Web;

use App\Models\Event;
use App\Models\Photo;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventsControllerTest extends TestCase
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
        $this->get('/events')->assertOk();
    }

    public function test_index_future_loads(): void
    {
        $this->get('/events/future')->assertOk();
    }

    public function test_index_today_loads(): void
    {
        $this->get('/events/today')->assertOk();
    }

    public function test_index_week_loads(): void
    {
        $this->get('/events/week')->assertOk();
    }

    public function test_index_past_loads(): void
    {
        $this->get('/events/past')->assertOk();
    }

    public function test_index_upcoming_loads(): void
    {
        $this->get('/events/upcoming')->assertOk();
    }

    public function test_show_loads_for_existing_event(): void
    {
        $event = Event::factory()->create();

        $this->get('/events/'.$event->slug)->assertOk();
    }

    public function test_create_form_requires_auth(): void
    {
        // Routes may use `verified` middleware which sends guests to
        // email/verify rather than /login; either is acceptable.
        $response = $this->get('/events/create');

        $response->assertStatus(302);
        $this->assertTrue(
            str_contains($response->headers->get('Location'), '/login')
            || str_contains($response->headers->get('Location'), '/email/verify')
        );
    }

    public function test_create_form_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/events/create')->assertOk();
    }

    public function test_edit_form_requires_auth(): void
    {
        $event = Event::factory()->create();

        $response = $this->get('/events/'.$event->slug.'/edit');

        $response->assertStatus(302);
        $this->assertTrue(
            str_contains($response->headers->get('Location'), '/login')
            || str_contains($response->headers->get('Location'), '/email/verify')
        );
    }

    /**
     * The tag listing previously filtered visibility in-memory and discarded the
     * result, leaking non-public events into the public page. A guest must only
     * ever see public events.
     */
    public function test_tag_listing_hides_non_public_events_from_guests(): void
    {
        $tag = Tag::factory()->create();

        $public = Event::factory()->create([
            'name' => 'Public Tag Probe Event',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addWeek(),
        ]);
        $private = Event::factory()->create([
            'name' => 'Secret Private Tag Probe Event',
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'start_at' => now()->addWeek(),
        ]);
        $public->tags()->attach($tag);
        $private->tags()->attach($tag);

        $response = $this->get('/events/tag/'.$tag->slug);

        $response->assertOk();
        $response->assertSee('Public Tag Probe Event');
        $response->assertDontSee('Secret Private Tag Probe Event');
    }

    /**
     * The card grid touches venue/type/tags/photos/etc. per event; those must be
     * eager-loaded so the tag listing is a fixed number of queries, not N per card.
     */
    public function test_tag_listing_eager_loads_event_relations_without_n_plus_one(): void
    {
        $tag = Tag::factory()->create();

        for ($i = 1; $i <= 3; $i++) {
            $event = Event::factory()->create([
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
                'start_at' => now()->addDays($i),
            ]);
            $event->photos()->attach(Photo::factory()->create(['is_primary' => 1]));
            $event->tags()->attach($tag);
        }

        DB::enableQueryLog();
        $this->get('/events/tag/'.$tag->slug)->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query');

        // The per-event primary-photo lookup must be eager-loaded, not run once per card.
        $perEventPhotoQueries = $queries->filter(fn ($q) => str_contains($q, 'event_photo')
            && str_contains($q, 'is_primary')
            && str_contains($q, 'limit 1'));

        $this->assertLessThanOrEqual(
            1,
            $perEventPhotoQueries->count(),
            'Tag-listing event primary photos should be eager-loaded, not queried per event (N+1).'
        );
    }
}
