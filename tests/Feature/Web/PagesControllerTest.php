<?php

namespace Tests\Feature\Web;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_guest_sees_login_and_register_buttons_in_home_hero(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $heroStart = strpos($html, '<!-- Hero Section -->');
        $heroEnd = strpos($html, '<!-- Upcoming Events Section -->');
        $hero = substr($html, $heroStart, $heroEnd - $heroStart);

        $this->assertStringContainsString('href="'.route('login').'"', $hero);
        $this->assertStringContainsString('>Login</span>', $hero);
        $this->assertStringContainsString('href="'.route('register').'"', $hero);
        $this->assertStringContainsString('>Register</span>', $hero);
    }

    public function test_authenticated_user_does_not_see_login_or_register_buttons_in_home_hero(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();
        $heroStart = strpos($html, '<!-- Hero Section -->');
        $heroEnd = strpos($html, '<!-- Upcoming Events Section -->');
        $hero = substr($html, $heroStart, $heroEnd - $heroStart);

        $this->assertStringNotContainsString('href="'.route('login').'"', $hero);
        $this->assertStringNotContainsString('href="'.route('register').'"', $hero);
    }

    public function test_home_alias_loads(): void
    {
        $this->get('/home')->assertOk();
    }

    public function test_about_page_loads(): void
    {
        $this->get('/about')->assertOk();
    }

    public function test_privacy_page_loads(): void
    {
        $this->get('/privacy')->assertOk();
    }

    public function test_tos_page_loads(): void
    {
        $this->get('/tos')->assertOk();
    }

    public function test_help_page_loads(): void
    {
        $this->get('/help')->assertOk();
    }

    public function test_popular_page_loads(): void
    {
        $this->get('/popular')->assertOk();
    }

    public function test_popular_page_shows_each_tag_with_its_public_event_count_and_one_thumbnail(): void
    {
        // The tag block is plain data (#2390): it once eager-loaded every
        // visible event of the top 24 tags (13k+ in prod, a php-fpm OOM), then
        // one event per tag through a window query that took a second.
        $tag = Tag::factory()->create();

        $events = [];
        foreach (['older' => 10, 'middle' => 5, 'latest' => 1] as $name => $daysAgo) {
            $events[$name] = Event::factory()->create([
                'visibility_id' => Visibility::VISIBILITY_PUBLIC,
                'start_at' => now()->subDays($daysAgo),
            ]);
            $events[$name]->photos()->attach(Photo::factory()->create([
                'is_primary' => 1,
                'path' => $name.'.jpg',
                'thumbnail' => $name.'_thumb.jpg',
            ])->id);
        }
        // a private event is neither counted nor shown
        $events['private'] = Event::factory()->create([
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'start_at' => now()->addDay(),
        ]);
        $events['private']->photos()->attach(Photo::factory()->create([
            'is_primary' => 1,
            'path' => 'private.jpg',
            'thumbnail' => 'private_thumb.jpg',
        ])->id);
        $tag->events()->attach(array_map(fn ($event) => $event->id, array_values($events)));

        $response = $this->get('/popular')->assertOk();

        $popularTag = collect($response->viewData('popularTags'))->firstWhere('slug', $tag->slug);

        $this->assertNotNull($popularTag, 'Tag with most events should appear in popular tags');
        $this->assertSame(
            ['slug' => $tag->slug, 'name' => $tag->name, 'events_count' => 3, 'thumbnail' => 'latest_thumb.jpg'],
            $popularTag
        );
        $response->assertSee('latest_thumb.jpg')->assertDontSee('private_thumb.jpg');
    }

    public function test_popular_page_loads_its_cards_without_a_query_per_card(): void
    {
        $venue = Entity::factory()->create(['entity_status_id' => EntityStatus::ACTIVE]);
        Event::factory()->count(3)->create([
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addDays(2),
            'venue_id' => $venue->id,
        ]);

        $response = $this->get('/popular')->assertOk();

        foreach (['photos', 'threads', 'visibility', 'venue'] as $relation) {
            $this->assertTrue($response->viewData('popularEvents')->first()->relationLoaded($relation), $relation);
        }
        foreach (['photos', 'links', 'aliases', 'entityStatus'] as $relation) {
            $this->assertTrue($response->viewData('popularEntities')->first()->relationLoaded($relation), $relation);
        }
    }

    public function test_radar_page_requires_auth(): void
    {
        $this->get('/radar')->assertRedirect('/login');
    }

    public function test_radar_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/radar')->assertOk();
    }

    public function test_all_modules_page_loads(): void
    {
        $this->get('/all-modules')->assertOk();
    }

    public function test_search_page_loads(): void
    {
        $this->get('/search')->assertOk();
    }

    public function test_tools_page_requires_auth(): void
    {
        $this->get('/tools')->assertRedirect('/login');
    }

    public function test_tools_page_redirects_non_admin_user(): void
    {
        // /tools requires the show_admin permission. A vanilla active user
        // does not have it, so they should be redirected home with a flash
        // (previously this branch called exit() and killed the response).
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/tools')->assertRedirect(route('home'));
    }
}
