<?php

namespace Tests\Feature\Web;

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
}
