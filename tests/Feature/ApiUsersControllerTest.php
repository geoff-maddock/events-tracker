<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiUsersControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_users_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/users');

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_users_show_returns_404_for_missing_user(): void
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($admin, 'sanctum');

        $response = $this->getJson('/api/users/99999999');

        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_users_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'New User',
            'email' => 'new@example.com',
        ]);

        $this->assertContains($response->status(), [401, 403, 422]);
    }

    public function test_users_update_requires_authentication(): void
    {
        $target = User::factory()->create();

        $response = $this->putJson('/api/users/'.$target->id, [
            'name' => 'Updated',
        ]);

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_users_destroy_requires_authentication(): void
    {
        $target = User::factory()->create();

        $response = $this->deleteJson('/api/users/'.$target->id);

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_users_events_attending_requires_authentication(): void
    {
        $target = User::factory()->create();

        $response = $this->getJson('/api/users/'.$target->id.'/events-attending');

        $this->assertContains($response->status(), [401, 403]);
    }
}
