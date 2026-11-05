<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Link;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The buttons that open the pop-out audio player (#2366).
 */
class PlayerLaunchTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    /**
     * The launch link's href for a queue query, as Blade escapes it.
     *
     * @param array<string, mixed> $query
     */
    private function launchHref(array $query): string
    {
        return e(route('player.show').'?'.http_build_query($query));
    }

    private function entityWithLink(string $url): Entity
    {
        $entity = Entity::factory()->create(['slug' => 'entity-'.Str::random(8)]);
        $entity->links()->attach(Link::factory()->create(['url' => $url]));

        return $entity;
    }

    public function test_the_sidebar_has_a_listen_link_and_the_launcher_script(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.$this->launchHref(['source' => 'upcoming']).'"', $html);
        $this->assertStringContainsString('data-player-launch', $html);
        $this->assertStringContainsString('target="arcane-player"', $html);
        $this->assertStringContainsString('/js/player-launcher.js', $html);
    }

    public function test_tag_page_plays_the_tag(): void
    {
        $tag = Tag::factory()->create(['slug' => 'goth', 'name' => 'goth']);

        $this->get('/tags/goth')
            ->assertOk()
            ->assertSee('Play this tag')
            ->assertSee('href="'.$this->launchHref(['source' => 'upcoming', 'filters' => ['tag' => $tag->slug]]).'"', false);
    }

    public function test_tagged_event_list_plays_the_list_with_its_tag(): void
    {
        $tag = Tag::factory()->create(['slug' => 'goth', 'name' => 'goth']);

        $html = $this->get('/events/tag/'.$tag->slug)->assertOk()->assertSee('Play this list')->getContent();

        $this->assertMatchesRegularExpression('#data-player-query="[^"]*filters%5Btag%5D=goth#', $html);
    }

    public function test_radar_offers_to_play_when_there_is_something_on_it(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/radar')->assertOk()->assertDontSee('Play my radar');

        Follow::create(['user_id' => $user->id, 'object_type' => 'entity', 'object_id' => Entity::factory()->create()->id]);

        $this->actingAs($user)->get('/radar')
            ->assertOk()
            ->assertSee('Play my radar')
            ->assertSee('href="'.$this->launchHref(['source' => 'radar']).'"', false);
    }

    public function test_entity_page_offers_add_to_player_only_with_an_audio_link(): void
    {
        $artist = $this->entityWithLink('https://artist.bandcamp.com/');
        $other = $this->entityWithLink('https://example.com/');

        $this->get('/entities/'.$artist->slug)
            ->assertOk()
            ->assertSee('aria-label="Add to player"', false)
            ->assertSee('data-player-mode="enqueue"', false)
            ->assertSee('href="'.$this->launchHref(['source' => 'entity', 'slug' => $artist->slug]).'"', false);

        $this->get('/entities/'.$other->slug)->assertOk()->assertDontSee('aria-label="Add to player"', false);
    }

    public function test_event_page_offers_add_to_player_when_its_lineup_has_audio(): void
    {
        $event = Event::factory()->create([
            'slug' => 'event-'.Str::random(8),
            'start_at' => now()->addDays(2),
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->get('/events/'.$event->slug)->assertOk()->assertDontSee('aria-label="Add to player"', false);

        $event->entities()->attach($this->entityWithLink('https://soundcloud.com/artist'));

        $this->get('/events/'.$event->slug)
            ->assertOk()
            ->assertSee('href="'.$this->launchHref(['source' => 'event', 'id' => $event->id]).'"', false);
    }

    public function test_series_page_offers_add_to_player_when_its_entities_have_audio(): void
    {
        $series = Series::factory()->create(['slug' => 'series-'.Str::random(8), 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $series->entities()->attach($this->entityWithLink('https://artist.bandcamp.com/album/x'));

        $this->get('/series/'.$series->slug)
            ->assertOk()
            ->assertSee('href="'.$this->launchHref(['source' => 'series', 'id' => $series->id]).'"', false);
    }

    public function test_past_events_list_has_no_play_button(): void
    {
        $this->get('/events/past')->assertOk()->assertDontSee('Play this list');
    }

    public function test_attending_and_venue_lists_play_their_own_queues(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user)->get('/events/attending')->assertOk()
            ->assertSee('href="'.$this->launchHref(['source' => 'attending']).'"', false);

        $venue = Entity::factory()->create(['slug' => 'some-venue']);
        $this->get('/events/venue/'.$venue->slug)->assertOk()
            ->assertSee('href="'.$this->launchHref(['source' => 'venue', 'slug' => 'some-venue']).'"', false);
    }

    public function test_by_date_list_plays_its_date_range(): void
    {
        $html = $this->get('/events/by-date/2026/11/14')->assertOk()->getContent();

        $this->assertStringContainsString(e(http_build_query(['filters' => ['start_at' => [
            'start' => '2026-11-14 00:00:01',
            'end' => '2026-11-14 23:59:59',
        ]]])), $html);
    }

    public function test_event_whose_only_audio_is_the_venue_has_no_add_button(): void
    {
        $event = Event::factory()->create(['slug' => 'event-'.Str::random(8), 'start_at' => now()->addDays(2), 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $venue = $this->entityWithLink('https://venue.bandcamp.com/');
        $venue->roles()->attach(\App\Models\Role::where('name', 'Venue')->firstOrFail());
        $event->entities()->attach($venue);

        $this->get('/events/'.$event->slug)->assertOk()->assertDontSee('aria-label="Add to player"', false);
    }

    public function test_series_with_audio_only_on_its_upcoming_lineups_offers_add_to_player(): void
    {
        $series = Series::factory()->create(['slug' => 'series-'.Str::random(8), 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $event = Event::factory()->create(['series_id' => $series->id, 'start_at' => now()->addDays(3), 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $event->entities()->attach($this->entityWithLink('https://soundcloud.com/lineup-artist'));

        $this->get('/series/'.$series->slug)->assertOk()
            ->assertSee('href="'.$this->launchHref(['source' => 'series', 'id' => $series->id]).'"', false);
    }

    public function test_entity_lists_play_their_filtered_list_when_something_is_playable(): void
    {
        $this->get('/entities/role/artist')->assertOk()->assertDontSee('Play this list');

        $artist = $this->entityWithLink('https://soundcloud.com/role-artist');
        $artist->update(['entity_status_id' => EntityStatus::ACTIVE]);
        $artist->roles()->attach(\App\Models\Role::where('name', 'Artist')->firstOrFail());

        $html = $this->get('/entities/role/artist')->assertOk()->assertSee('Play this list')->getContent();
        $this->assertMatchesRegularExpression('#data-player-query="source=entities[^"]*filters%5Brole%5D=artist#', $html);

        $html = $this->get('/entities')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#data-player-query="source=entities[^"]*filters%5Bentity_status%5D=Active[^"]*sort=entities.created_at&amp;direction=desc#', $html);
    }

    public function test_following_list_plays_who_you_follow(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $artist = $this->entityWithLink('https://soundcloud.com/followed-artist');
        Follow::create(['user_id' => $user->id, 'object_type' => 'entity', 'object_id' => $artist->id]);

        $html = $this->actingAs($user)->get('/entities/following')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#data-player-query="source=entities[^"]*filters%5Bdisplay_type%5D=following#', $html);
    }
}
