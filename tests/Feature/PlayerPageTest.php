<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('data-queue-url="'.route('player.queue').'"', false)
            ->assertSee('data-signed-in="0"', false)
            ->assertSee('https://w.soundcloud.com/player/api.js', false)
            ->assertSee('/js/player.js', false)
            // standalone window: no site sidebar
            ->assertDontSee('sidebar-desktop-tw', false)
            ->assertDontSee('mobile-menu-toggle-top', false);
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
