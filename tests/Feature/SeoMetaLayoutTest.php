<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetaLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    protected function base(): string
    {
        return rtrim(config('app.url'), '/');
    }

    public function test_listing_page_gets_default_self_canonical_and_no_robots_meta(): void
    {
        $response = $this->get('/events');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.$this->base().'/events">', false);
        $response->assertDontSee('noindex', false);
    }

    public function test_listing_with_filter_params_gets_clean_canonical_and_noindex(): void
    {
        $response = $this->get('/events?sort=name&direction=desc');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.$this->base().'/events">', false);
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_listing_with_utm_params_is_not_noindexed(): void
    {
        $response = $this->get('/events?utm_source=newsletter');

        $response->assertOk();
        $response->assertDontSee('noindex', false);
    }

    public function test_event_show_canonicalizes_to_slug_route_even_by_id(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $response = $this->get('/events/'.$event->id);

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.$this->base().'/events/'.$event->slug.'">', false);
    }

    public function test_series_show_canonicalizes_to_slug_route(): void
    {
        $series = Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $response = $this->get('/series/'.$series->slug);

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.$this->base().'/series/'.$series->slug.'">', false);
    }

    public function test_tag_show_canonicalizes_to_slug_route(): void
    {
        $tag = Tag::factory()->create(['name' => 'Canonical Probe', 'slug' => 'canonical-probe']);

        $response = $this->get('/tags/canonical-probe');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.$this->base().'/tags/canonical-probe">', false);
    }

    public function test_entity_show_keeps_its_existing_canonical_override(): void
    {
        $entity = \App\Models\Entity::factory()->create();

        $response = $this->get('/entities/'.$entity->slug);

        $response->assertOk();
        $response->assertSee('rel="canonical"', false);
    }

    public function test_user_profile_is_noindexed(): void
    {
        $user = User::factory()->create();

        // first hit creates a missing profile and redirects back to itself
        $response = $this->followingRedirects()->get('/users/'.$user->id);

        $response->assertOk();
        $response->assertSee('noindex', false);
    }

    public function test_user_attending_page_is_noindexed(): void
    {
        $user = User::factory()->create();

        $response = $this->get('/users/'.$user->id.'/attending');

        $response->assertOk();
        $response->assertSee('noindex', false);
    }

    public function test_og_url_uses_canonical_host_without_query(): void
    {
        $response = $this->get('/events?sort=name');

        $response->assertOk();
        $response->assertSee('<meta property="og:url" content="'.$this->base().'/events">', false);
    }
}
