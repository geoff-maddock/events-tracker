<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Link;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\Embeds\OembedExtractor;
use App\Services\Embeds\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EventEmbedsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function bindFakeProvider(): FakeEmbedProvider
    {
        $fake = new FakeEmbedProvider();
        $this->app->instance(Provider::class, $fake);

        return $fake;
    }

    public function test_load_embeds_by_slug_returns_json_payload(): void
    {
        $this->bindFakeProvider();

        $event = Event::factory()->create([
            'description' => 'Stream: https://soundcloud.com/example/track-one',
        ]);

        $response = $this->getJson('/events/'.$event->slug.'/embeds');

        $response->assertOk()
            ->assertJsonPath('message', 'Embeds loaded successfully')
            ->assertJsonStructure(['data', 'message']);

        $this->assertNotEmpty($response->json('data'));
        $this->assertStringContainsString('soundcloud', $response->json('data.0'));
    }

    public function test_load_embeds_by_slug_returns_404_for_missing_event(): void
    {
        $response = $this->getJson('/events/this-event-does-not-exist/embeds');

        $response->assertStatus(404)
            ->assertJsonPath('error', 'Event not found');
    }

    public function test_embeds_are_cached_between_requests(): void
    {
        $fake = $this->bindFakeProvider();

        $event = Event::factory()->create([
            'description' => 'Stream: https://soundcloud.com/example/track-cache',
        ]);

        $this->getJson('/events/'.$event->slug.'/embeds')->assertOk();
        $firstCallCount = $fake->requestCount;
        $this->assertGreaterThan(0, $firstCallCount, 'Provider should be hit on first request');

        // Re-instantiate the extractor between calls to prove caching survives across requests.
        $this->app->forgetInstance(OembedExtractor::class);

        $this->getJson('/events/'.$event->slug.'/embeds')->assertOk();

        $this->assertSame(
            $firstCallCount,
            $fake->requestCount,
            'Second request should be served from cache and not hit the provider again'
        );
    }

    public function test_cache_invalidates_when_event_is_updated(): void
    {
        $fake = $this->bindFakeProvider();

        $event = Event::factory()->create([
            'description' => 'Stream: https://soundcloud.com/example/track-invalidate',
        ]);

        $this->getJson('/events/'.$event->slug.'/embeds')->assertOk();
        $countAfterFirst = $fake->requestCount;

        // Edit the event's link and bump updated_at to invalidate the slug-based
        // cache key (a distinct timestamp, since touch() within the same second
        // would collide with the first key). An unchanged URL would be served from
        // the per-URL source cache, so the edit changes it.
        $event->description = 'Stream: https://soundcloud.com/example/track-invalidate-edited';
        $event->updated_at = $event->updated_at->copy()->addSeconds(5);
        $event->save();

        $this->getJson('/events/'.$event->slug.'/embeds')->assertOk();

        $this->assertGreaterThan(
            $countAfterFirst,
            $fake->requestCount,
            'After the event is touched, the cache key should change and the provider should be hit again'
        );
    }

    public function test_event_show_does_not_block_on_embeds(): void
    {
        // The show controller should no longer call the extractor synchronously.
        // Bind a provider that would explode if invoked from the show pipeline.
        $boom = new class extends Provider {
            public function request(string $url): void
            {
                throw new \RuntimeException('Show controller should not hit the embed provider synchronously');
            }
        };
        $this->app->instance(Provider::class, $boom);

        $event = Event::factory()->create([
            'description' => 'Stream: https://soundcloud.com/example/should-defer',
        ]);

        $this->get('/events/'.$event->slug)->assertOk();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    private function listedEvent(string $description): Event
    {
        return Event::factory()->create([
            'description' => $description,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
            'start_at' => now()->addWeek(),
        ]);
    }

    /**
     * A list card leaves its player placeholder out, and so makes no request, when
     * nothing on the event could become a player (#2389).
     */
    public function test_card_has_no_placeholder_when_the_event_has_nothing_to_play(): void
    {
        $event = $this->listedEvent('Nothing to play here: https://example.com/tickets');
        $entity = Entity::factory()->create();
        $entity->links()->attach(Link::factory()->create(['url' => 'https://example.com/artist'])->id);
        $event->entities()->attach($entity->id);

        $this->get('/events')->assertOk()
            ->assertSee($event->name)
            ->assertDontSee('id="playlist-events-'.$event->id.'"', false);
    }

    public function test_card_has_a_versioned_placeholder_when_the_description_has_an_audio_link(): void
    {
        $event = $this->listedEvent('Stream: https://soundcloud.com/example/track-one');

        $this->get('/events')->assertOk()
            ->assertSee('id="playlist-events-'.$event->id.'"', false)
            ->assertSee('data-version="'.OembedExtractor::versionFor($event->fresh()).'"', false);
    }

    /** Embeds are often added later, as a link on one of the event's entities. */
    public function test_the_placeholder_appears_once_a_related_entity_gets_an_audio_link(): void
    {
        $event = $this->listedEvent('Nothing to play here.');
        $entity = Entity::factory()->create();
        $event->entities()->attach($entity->id);
        $placeholder = 'id="playlist-events-'.$event->id.'"';

        $this->get('/events')->assertOk()->assertDontSee($placeholder, false);

        $entity->links()->attach(Link::factory()->create(['url' => 'https://artist.bandcamp.com/album/one'])->id);

        $this->get('/events')->assertOk()->assertSee($placeholder, false);
    }

    public function test_may_have_embeds_is_maybe_when_links_are_not_loaded(): void
    {
        $event = $this->listedEvent('Nothing to play here.');
        $entity = Entity::factory()->create();
        $event->entities()->attach($entity->id);

        $this->assertTrue(OembedExtractor::mayHaveEmbeds(Event::find($event->id)));
        $this->assertTrue(OembedExtractor::mayHaveEmbeds(Event::with('entities')->find($event->id)));
        $this->assertFalse(OembedExtractor::mayHaveEmbeds(Event::with('entities.links')->find($event->id)));

        $this->assertTrue(OembedExtractor::mayHaveEmbeds(Entity::withoutEagerLoads()->find($entity->id)));
        $this->assertFalse(OembedExtractor::mayHaveEmbeds(Entity::with('links')->find($entity->id)));
    }

    /**
     * The version keys the server cache and the browser's, so it has to move with
     * anything that can change the embeds.
     */
    public function test_version_changes_with_the_event_its_entities_and_their_links(): void
    {
        $event = $this->listedEvent('Nothing to play here.');
        $older = Entity::factory()->create();
        $newer = Entity::factory()->create();
        $link = Link::factory()->create(['url' => 'https://soundcloud.com/example/entity-track']);
        $older->links()->attach($link->id);

        // everything last saved well in the past, the event most recently
        $older->forceFill(['updated_at' => now()->subDays(3)])->saveQuietly();
        $newer->forceFill(['updated_at' => now()->subDays(2)])->saveQuietly();
        $event->forceFill(['updated_at' => now()->subDay()])->saveQuietly();
        $version = fn () => OembedExtractor::versionFor(Event::find($event->id));
        $last = $version();

        $step = function (string $what) use (&$last, $version) {
            $this->assertNotSame($last, $version(), $what);
            $last = $version();
        };

        // an entity older than the event is added, nothing else saved
        $event->entities()->attach($older->id);
        $step('entity attached');

        // back to the one entity: the same inputs as before, so the same version
        $withOne = $last;
        $event->entities()->attach($newer->id);
        $step('second entity attached');
        $event->entities()->detach($newer->id);
        $step('entity detached');
        $this->assertSame($withOne, $version());

        $older->links()->attach(Link::factory()->create(['url' => 'https://artist.bandcamp.com/album/two'])->id);
        $step('link attached');

        $older->forceFill(['updated_at' => now()->subDays(3)])->saveQuietly();
        $step('reset');
        $link->update(['url' => 'https://soundcloud.com/example/entity-track-two']);
        $step('link changed');

        $older->forceFill(['updated_at' => now()->subDays(4)])->saveQuietly();
        $step('reset');
        $link->delete();
        $step('link deleted');
    }

    public function test_removing_a_link_through_the_entity_page_touches_the_entity(): void
    {
        $owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $owner->assignGroup('admin');
        $entity = Entity::factory()->create(['created_by' => $owner->id]);
        $link = Link::factory()->create(['url' => 'https://soundcloud.com/example/entity-track']);
        $entity->links()->attach($link->id);
        $entity->forceFill(['updated_at' => now()->subDay()])->saveQuietly();

        $this->actingAs($owner)->delete('/entities/'.$entity->slug.'/links/'.$link->id)->assertRedirect();

        $this->assertNull(Link::find($link->id));
        $this->assertTrue($entity->fresh()->updated_at->gt(now()->subMinute()));
    }

    /** An empty answer can be a provider that did not respond: it is kept for an hour, not a week. */
    public function test_an_empty_result_is_cached_for_a_shorter_time(): void
    {
        $this->bindFakeProvider();
        $event = $this->listedEvent('Broken: https://artist.bandcamp.com/album/gone');

        $this->getJson('/events/'.$event->slug.'/minimal-embeds')->assertOk()->assertJsonPath('data', []);

        $this->travel(OembedExtractor::FAILED_SOURCES_TTL_SECONDS + 60)->seconds();
        $fake = $this->bindFakeProvider();
        $this->app->forgetInstance(OembedExtractor::class);
        $this->getJson('/events/'.$event->slug.'/minimal-embeds')->assertOk();

        $this->assertGreaterThan(0, $fake->requestCount, 'the provider is asked again once the hour is up');
    }
}

/**
 * Minimal Provider double that returns a canned SoundCloud-shaped oembed payload
 * without making any network calls, and tracks how many times it was invoked.
 */
class FakeEmbedProvider extends Provider
{
    public int $requestCount = 0;

    public function request(string $url): void
    {
        $this->requestCount++;

        if (str_contains($url, 'soundcloud.com/oembed')) {
            $this->setResponse(json_encode([
                'html' => '<iframe src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/123&visual=true"></iframe>',
            ]));
            return;
        }

        // Anything else (bandcamp scrape, container fetch, etc.) gets an empty response.
        $this->setResponse('');
    }

    public function requestWithoutRedirects(string $url): ?string
    {
        $this->requestCount++;
        $this->setResponse('');

        return null;
    }
}
