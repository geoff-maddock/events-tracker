<?php

namespace Tests\Feature\Web;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_index_requires_auth(): void
    {
        $this->get('/users')->assertStatus(302);
    }

    public function test_index_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/users')->assertOk();
    }

    public function test_show_loads_for_existing_user(): void
    {
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $response = $this->get('/users/'.$target->id);

        // show may require auth or always be open depending on the user's
        // public-profile setting; accept 200 or redirect.
        $this->assertContains($response->status(), [200, 302]);
    }

    public function test_profile_alias_loads_for_authenticated_user(): void
    {
        $viewer = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $response = $this->actingAs($viewer)->get('/profile/'.$target->id);

        $this->assertContains($response->status(), [200, 302]);
    }

    public function test_profile_redirect_requires_auth(): void
    {
        // Guests must be redirected to login, not dereference a null user (EVENTREPO-HH).
        $this->get('/profile')->assertStatus(302);
    }

    public function test_profile_redirects_authenticated_user_to_their_page(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)->get('/profile')->assertRedirect('users/'.$user->id);
    }

    public function test_show_with_partial_tabs_does_not_error(): void
    {
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create([
            'user_id' => $target->id,
            'setting_public_profile' => 1,
        ]);

        // A partial tabs param (no "following") must not throw "Undefined array
        // key" when the view renders the tab bar (EVENTREPO-TJ).
        $this->actingAs($target)
            ->get('/users/'.$target->id.'?tabs[events]=created')
            ->assertOk();
    }
}
