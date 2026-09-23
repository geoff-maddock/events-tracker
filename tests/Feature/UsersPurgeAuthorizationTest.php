<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * POST /purge deletes every pending user, so it needs grant_access.
 */
class UsersPurgeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_guest_cannot_purge_pending_users(): void
    {
        $pending = User::factory()->create(['user_status_id' => UserStatus::PENDING]);

        $this->post(route('users.purge'))->assertRedirect();

        $this->assertNotNull(User::find($pending->id));
    }

    public function test_member_cannot_purge_pending_users(): void
    {
        $member = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $pending = User::factory()->create(['user_status_id' => UserStatus::PENDING]);

        $this->actingAs($member)->post(route('users.purge'))->assertForbidden();

        $this->assertNotNull(User::find($pending->id));
    }

    public function test_admin_can_purge_pending_users(): void
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');
        $pending = User::factory()->create(['user_status_id' => UserStatus::PENDING]);

        $this->actingAs($admin->fresh())->post(route('users.purge'))->assertRedirect();

        $this->assertNull(User::find($pending->id));
    }

    public function test_debug_dispatch_routes_are_gone(): void
    {
        $this->get('/events/dispatch')->assertNotFound();
        $this->get('/update')->assertNotFound();
    }
}
