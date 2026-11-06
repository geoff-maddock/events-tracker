<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The pop-out audio player window (#2366).
 */
class PlayerPageTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_guests_get_the_player_shell(): void
    {
        $this->get('/player?source=upcoming&filters[tag]=goth')
            ->assertOk()
            // public queues show in search results (personal ones don't, below)
            ->assertDontSee('name="robots"', false)
            ->assertSee('<link rel="canonical" href="'.e(route('player.show', ['source' => 'upcoming', 'filters' => ['tag' => 'goth']])).'">', false)
            ->assertSee('data-queue-url="'.route('player.queue').'"', false)
            ->assertSee('data-signed-in="0"', false)
            ->assertSee('https://w.soundcloud.com/player/api.js', false)
            ->assertSee('/js/player.js', false)
            // standalone window: no site sidebar
            ->assertDontSee('sidebar-desktop-tw', false)
            ->assertDontSee('mobile-menu-toggle-top', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonLd(string $uri): array
    {
        $html = (string) $this->get($uri)->assertOk()->getContent();

        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match));

        return json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_json_ld_describes_an_event_lineup_with_its_flyer(): void
    {
        Storage::fake('external');
        $event = Event::factory()->create(['name' => 'Night Shift', 'visibility_id' => Visibility::VISIBILITY_PUBLIC]);
        $photo = (new Photo())->forceFill([
            'name' => 'flyer.jpg',
            'path' => 'photos/flyer.jpg',
            'thumbnail' => 'photos/tn-flyer.jpg',
            'caption' => 'Flyer',
            'is_primary' => 1,
            'is_public' => 1,
            'is_approved' => 1,
        ]);
        $photo->save();
        $event->photos()->attach($photo->id);
        $flyer = Storage::disk('external')->url('photos/flyer.jpg');

        $jsonLd = $this->jsonLd('/player?source=event&id='.$event->id.'&utm_source=x');

        $this->assertSame('WebPage', $jsonLd['@type']);
        $this->assertSame('Night Shift lineup | '.config('app.app_name').' Player', $jsonLd['name']);
        $this->assertSame($flyer, $jsonLd['image']);
        $this->assertStringContainsString('tracks from the lineup of Night Shift', $jsonLd['description']);
        $this->assertSame('WebApplication', $jsonLd['mainEntity']['@type']);
        $this->assertSame(['@type' => 'Event', 'name' => 'Night Shift', 'url' => route('events.show', $event), 'image' => $flyer], $jsonLd['about']);
        $this->assertStringNotContainsString('utm_source', $jsonLd['url']);

        $this->get('/player?source=event&id='.$event->id)
            ->assertSee('<meta property="og:image" content="'.$flyer.'">', false);
    }

    public function test_json_ld_never_names_an_event_the_viewer_cannot_see(): void
    {
        $event = Event::factory()->create(['name' => 'Secret Party', 'visibility_id' => Visibility::VISIBILITY_PRIVATE]);

        $jsonLd = $this->jsonLd('/player?source=event&id='.$event->id);

        $this->assertArrayNotHasKey('about', $jsonLd);
        $this->assertStringNotContainsString('Secret Party', (string) json_encode($jsonLd));
        $this->assertSame(url(\App\Services\Player\PlayerPageSchema::DEFAULT_IMAGE), $jsonLd['image']);
    }

    public function test_json_ld_describes_a_filtered_list(): void
    {
        $jsonLd = $this->jsonLd('/player?source=upcoming&filters[tag]=goth');

        $this->assertSame('Upcoming goth shows | '.config('app.app_name').' Player', $jsonLd['name']);
        $this->assertStringContainsString('upcoming goth shows', $jsonLd['description']);
        $this->assertSame(url(\App\Services\Player\PlayerPageSchema::DEFAULT_IMAGE), $jsonLd['image']);
    }

    public function test_personal_queues_stay_out_of_search_results(): void
    {
        $this->get('/player?source=radar')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_signed_in_users_can_star(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/player')
            ->assertOk()
            ->assertSee('data-signed-in="1"', false)
            ->assertSee('data-follow-url="'.url('entities/__ID__/follow').'"', false);
    }

    public function test_policy_allows_the_soundcloud_widget_api(): void
    {
        $policy = (string) $this->get('/player')->headers->get('Content-Security-Policy-Report-Only');

        $this->assertMatchesRegularExpression('#script-src [^;]*https://w\.soundcloud\.com#', $policy);
        $this->assertMatchesRegularExpression('#frame-src [^;]*https://w\.soundcloud\.com#', $policy);
        $this->assertMatchesRegularExpression('#frame-src [^;]*https://bandcamp\.com#', $policy);
    }

    public function test_the_player_script_uses_the_widget_api_and_player_channel(): void
    {
        $script = (string) file_get_contents(public_path('js/player.js'));

        // talks to the endpoints the page advertises and the channel launch buttons use
        $this->assertStringContainsString("BroadcastChannel('arcane-player')", $script);
        $this->assertStringContainsString('SC.Widget', $script);
    }
}
