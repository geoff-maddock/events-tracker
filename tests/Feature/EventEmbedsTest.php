<?php

namespace Tests\Feature;

use App\Models\Event;
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

        // Bump updated_at to invalidate the slug-based cache key. Force a distinct
        // timestamp since touch() within the same second would collide with the
        // first-request cache key.
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
}
