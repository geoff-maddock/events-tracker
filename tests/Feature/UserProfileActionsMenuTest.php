<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * On a phone the profile's action bar wrapped and the right-hand group (Back
 * and the actions menu) landed on the left, so the menu opened off-screen
 * (#2157). The iCal links moved into the actions menu, which is pinned right.
 */
class UserProfileActionsMenuTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function user(bool $public = true): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create(['user_id' => $user->id, 'setting_public_profile' => $public ? 1 : 0]);

        return $user;
    }

    public function test_the_ical_links_live_in_the_actions_menu_which_stays_right(): void
    {
        $user = $this->user();

        $html = $this->get("/users/{$user->id}")->assertOk()->getContent();

        // no separate iCal dropdown button in the bar
        $this->assertStringNotContainsString('<i class="bi bi-chevron-down ml-2 text-xs"></i>', $html);
        $this->assertStringContainsString('<div class="flex items-center gap-2 ml-auto">', $html);
        // a guest on a public profile gets the menu with just the calendar links
        $menu = substr($html, strpos($html, 'title="More actions"'));
        $this->assertStringContainsString(route('users.attendingIcal', ['id' => $user->id]), $menu);
        $this->assertStringContainsString(route('users.interestedIcal', ['id' => $user->id]), $menu);
        $this->assertStringNotContainsString('Edit Profile', $html);
    }

    public function test_the_owner_gets_the_calendar_links_and_their_own_actions(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get("/users/{$user->id}")->assertOk()
            ->assertSeeInOrder(['title="More actions"', 'Attending iCal', 'Interested iCal', 'Edit Profile', 'Export My Data'], false);
    }

    public function test_a_private_profile_shows_no_menu_to_others(): void
    {
        $user = $this->user(public: false);

        $this->actingAs($this->user())->get("/users/{$user->id}")->assertOk()
            ->assertDontSee('title="More actions"', false)
            ->assertDontSee('Attending iCal');
    }
}
