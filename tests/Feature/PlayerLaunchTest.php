<?php

namespace Tests\Feature;

use App\Models\Entity;
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
}
