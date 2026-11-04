<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Follow;
use App\Models\Link;
use App\Models\ResponseType;
use App\Models\Role;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use App\Services\Embeds\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The audio player's queue and per-entity source endpoints (#2366).
 */
class PlayerQueueTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function artist(string $url = 'https://soundcloud.com/some-artist', ?string $role = 'Artist'): Entity
    {
        $entity = Entity::factory()->create(['slug' => 'artist-'.Str::random(8)]);
        $entity->links()->attach(Link::factory()->create(['url' => $url]));

        if ($role !== null) {
            $entity->roles()->attach(Role::where('name', $role)->firstOrFail());
        }

        return $entity;
    }

    private function eventWith(array $entities, array $attributes = []): Event
    {
        $event = Event::factory()->create(array_merge([
            'slug' => 'event-'.Str::random(8),
            'start_at' => now()->addDays(3),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ], $attributes));
        $event->entities()->attach(collect($entities)->pluck('id'));

        return $event;
    }

    /**
     * @return list<int>
     */
    private function queueEntityIds(string $query = '', ?User $user = null): array
    {
        $request = $user ? $this->actingAs($user) : $this;
        $response = $request->getJson('/player/queue'.$query)->assertOk();

        return array_map(fn (array $item) => $item['entity']['id'], $response->json('items'));
    }

    public function test_upcoming_queue_lists_artists_with_audio_links_in_event_order(): void
    {
        $later = $this->artist('https://later.bandcamp.com/');
        $sooner = $this->artist('https://soundcloud.com/sooner');
        $noAudio = Entity::factory()->create(['slug' => 'no-audio-'.Str::random(6)]);
        $noAudio->links()->attach(Link::factory()->create(['url' => 'https://example.com/']));

        $this->eventWith([$later], ['start_at' => now()->addDays(10)]);
        $soonEvent = $this->eventWith([$sooner, $noAudio], ['start_at' => now()->addDays(2)]);

        $response = $this->getJson('/player/queue')->assertOk();

        $this->assertSame([$sooner->id, $later->id], array_column(array_column($response->json('items'), 'entity'), 'id'));
        $response->assertJsonPath('items.0.event.id', $soonEvent->id)
            ->assertJsonPath('items.0.providers', ['soundcloud'])
            ->assertJsonPath('items.1.providers', ['bandcamp'])
            ->assertJsonPath('items.0.entity.followed', false)
            ->assertJsonPath('items.0.sources_url', route('player.sources', ['entity' => $sooner->slug]));
    }

    public function test_an_entity_on_several_events_is_queued_once_for_its_next_event(): void
    {
        $artist = $this->artist();
        $first = $this->eventWith([$artist], ['start_at' => now()->addDays(1)]);
        $this->eventWith([$artist], ['start_at' => now()->addDays(5)]);

        $response = $this->getJson('/player/queue')->assertOk();

        $this->assertCount(1, $response->json('items'));
        $response->assertJsonPath('items.0.event.id', $first->id);
    }

    public function test_events_the_viewer_cannot_see_and_past_events_do_not_contribute(): void
    {
        $owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $private = $this->artist();
        $past = $this->artist();
        $public = $this->artist();

        $this->eventWith([$private], ['visibility_id' => Visibility::VISIBILITY_PRIVATE, 'created_by' => $owner->id]);
        $this->eventWith([$past], ['start_at' => now()->subDays(3)]);
        $this->eventWith([$public]);

        $this->assertSame([$public->id], $this->queueEntityIds());
        $this->assertEqualsCanonicalizing([$private->id, $public->id], $this->queueEntityIds('', $owner));
    }

    public function test_venue_only_entities_are_skipped_but_venues_that_perform_are_kept(): void
    {
        $venue = $this->artist('https://venue.bandcamp.com/', 'Venue');
        $performingVenue = $this->artist('https://venue-band.bandcamp.com/', 'Venue');
        $performingVenue->roles()->attach(Role::where('name', 'Band')->firstOrFail());
        $noRoles = $this->artist('https://soundcloud.com/no-roles', null);

        $this->eventWith([$venue, $performingVenue, $noRoles]);

        $this->assertEqualsCanonicalizing([$performingVenue->id, $noRoles->id], $this->queueEntityIds());
    }

    public function test_event_list_filters_narrow_the_queue(): void
    {
        $tag = Tag::factory()->create(['slug' => 'goth']);
        $goth = $this->artist();
        $other = $this->artist();

        $this->eventWith([$goth])->tags()->attach($tag);
        $this->eventWith([$other]);

        $this->assertSame([$goth->id], $this->queueEntityIds('?filters[tag]=goth'));
    }

    public function test_filtered_queue_mirrors_the_list_with_upcoming_then_recent_past(): void
    {
        $tag = Tag::factory()->create(['slug' => 'goth']);
        $upcoming = $this->artist();
        $recentPast = $this->artist();
        $olderPast = $this->artist();
        $untagged = $this->artist();

        $this->eventWith([$olderPast], ['start_at' => now()->subMonths(6)])->tags()->attach($tag);
        $this->eventWith([$upcoming], ['start_at' => now()->addDays(4)])->tags()->attach($tag);
        $recentEvent = $this->eventWith([$recentPast], ['start_at' => now()->subWeek()]);
        $recentEvent->tags()->attach($tag);
        $this->eventWith([$untagged], ['start_at' => now()->subDays(2)]);

        $response = $this->getJson('/player/queue?filters[tag]=goth')->assertOk();

        $this->assertSame(
            [$upcoming->id, $recentPast->id, $olderPast->id],
            array_column(array_column($response->json('items'), 'entity'), 'id')
        );
        $response->assertJsonPath('items.0.event.past', false)
            ->assertJsonPath('items.1.event.past', true)
            ->assertJsonPath('items.1.event.id', $recentEvent->id);
    }

    public function test_radar_requires_sign_in(): void
    {
        $this->getJson('/player/queue?source=radar')->assertStatus(401);
    }

    public function test_radar_queue_uses_attending_and_followed_events(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $attended = $this->artist();
        $attendedEvent = $this->eventWith([$attended]);
        EventResponse::create(['event_id' => $attendedEvent->id, 'user_id' => $user->id, 'response_type_id' => ResponseType::ATTENDING]);

        $followed = $this->artist();
        $this->eventWith([$followed]);
        Follow::create(['user_id' => $user->id, 'object_type' => 'entity', 'object_id' => $followed->id]);

        $unrelated = $this->artist();
        $this->eventWith([$unrelated]);

        $response = $this->actingAs($user)->getJson('/player/queue?source=radar')->assertOk();
        $items = collect($response->json('items'))->keyBy('entity.id');

        $this->assertEqualsCanonicalizing([$attended->id, $followed->id], $items->keys()->all());
        $this->assertTrue($items[$followed->id]['entity']['followed']);
        $this->assertFalse($items[$attended->id]['entity']['followed']);
    }

    public function test_radar_queue_puts_attending_before_recommended_like_the_radar_page(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        // recommended shows are sooner, so date order alone would put them first
        $followedSoon = $this->artist();
        $followedSoon->update(['name' => 'Zz Followed']);
        $this->eventWith([$followedSoon], ['start_at' => now()->addDays(1)]);
        Follow::create(['user_id' => $user->id, 'object_type' => 'entity', 'object_id' => $followedSoon->id]);

        $attendedLater = $this->artist();
        $attendedLatest = $this->artist();
        // artists within one event are ordered by name; pin it so the order is fixed
        $attendedLatest->update(['name' => 'Aa Attended']);
        $later = $this->eventWith([$attendedLater], ['start_at' => now()->addDays(5)]);
        $latest = $this->eventWith([$attendedLatest, $followedSoon], ['start_at' => now()->addDays(9)]);
        foreach ([$later, $latest] as $event) {
            EventResponse::create(['event_id' => $event->id, 'user_id' => $user->id, 'response_type_id' => ResponseType::ATTENDING]);
        }

        $response = $this->actingAs($user)->getJson('/player/queue?source=radar')->assertOk();

        // attending in date order, then recommended; the followed artist also on an
        // attended show is queued once, in the attending group, with that show
        $this->assertSame(
            [$attendedLater->id, $attendedLatest->id, $followedSoon->id],
            array_column(array_column($response->json('items'), 'entity'), 'id')
        );
        $response->assertJsonPath('items.2.event.id', $latest->id);
    }

    public function test_event_source_plays_a_lineup_even_after_the_event(): void
    {
        $artist = $this->artist();
        $event = $this->eventWith([$artist], ['start_at' => now()->subWeek()]);

        $this->assertSame([$artist->id], $this->queueEntityIds('?source=event&id='.$event->id));
    }

    public function test_event_source_hides_events_the_viewer_cannot_see(): void
    {
        $event = $this->eventWith([$this->artist()], [
            'visibility_id' => Visibility::VISIBILITY_PRIVATE,
            'created_by' => User::factory()->create(['user_status_id' => UserStatus::ACTIVE])->id,
        ]);

        $this->getJson('/player/queue?source=event&id='.$event->id)->assertNotFound();
    }

    public function test_series_source_lists_series_entities_then_upcoming_lineups(): void
    {
        $resident = $this->artist();
        $guest = $this->artist();
        $series = Series::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $series->entities()->attach($resident);
        $this->eventWith([$guest, $resident], ['series_id' => $series->id]);

        $response = $this->getJson('/player/queue?source=series&id='.$series->id)->assertOk();

        $this->assertSame([$resident->id, $guest->id], array_column(array_column($response->json('items'), 'entity'), 'id'));
        $response->assertJsonPath('items.0.event', null);
    }

    public function test_entity_source_includes_the_entity_with_its_next_event(): void
    {
        $artist = $this->artist();
        $event = $this->eventWith([$artist]);

        $this->getJson('/player/queue?source=entity&slug='.$artist->slug)
            ->assertOk()
            ->assertJsonPath('items.0.entity.id', $artist->id)
            ->assertJsonPath('items.0.event.id', $event->id);

        $this->getJson('/player/queue?source=entity&slug=nobody-here')->assertNotFound();
    }

    public function test_invalid_sources_are_rejected(): void
    {
        $this->getJson('/player/queue?source=everything')->assertStatus(422);
        $this->getJson('/player/queue?source=event')->assertStatus(422);
    }

    public function test_sources_endpoint_resolves_and_caches_an_entitys_links(): void
    {
        $provider = new FakePlayerProvider();
        $this->app->instance(Provider::class, $provider);

        $artist = $this->artist('https://soundcloud.com/some-artist');
        $artist->links()->attach(Link::factory()->create(['url' => 'https://example.com/not-audio']));

        $this->getJson('/player/entities/'.$artist->slug.'/sources')
            ->assertOk()
            ->assertJsonPath('entity.id', $artist->id)
            ->assertJsonCount(1, 'sources')
            ->assertJsonPath('sources.0.provider', 'soundcloud')
            ->assertJsonPath('sources.0.url', 'https://soundcloud.com/some-artist')
            ->assertJsonPath('sources.0.title', 'Some Track')
            ->assertJsonPath('sources.0.src', 'https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/users/1&visual=true');

        $this->assertSame(1, $provider->requestCount);

        $this->getJson('/player/entities/'.$artist->slug.'/sources')->assertOk()->assertJsonCount(1, 'sources');
        $this->assertSame(1, $provider->requestCount, 'second request is served from the per-link cache');
    }
}

/**
 * Serves a canned SoundCloud oEmbed response and counts requests.
 */
class FakePlayerProvider extends Provider
{
    public int $requestCount = 0;

    public function request(string $url): void
    {
        $this->requestCount++;

        $this->setResponse(str_contains($url, 'soundcloud.com/oembed') ? (string) json_encode([
            'title' => 'Some Track',
            'html' => '<iframe src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/users/1&visual=true"></iframe>',
        ]) : '');
    }

    public function requestWithoutRedirects(string $url): ?string
    {
        $this->requestCount++;
        $this->setResponse('');

        return null;
    }
}
