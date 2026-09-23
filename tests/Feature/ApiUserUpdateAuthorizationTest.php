<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PUT /api/users/{user} is limited to the user themselves or grant_access,
 * and only grant_access may change status or group membership.
 */
class ApiUserUpdateAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $user->profile()->create([]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    public function test_user_cannot_update_another_user(): void
    {
        $actor = $this->makeUser();
        $victim = $this->makeUser();
        $originalEmail = $victim->email;

        $this->actingAs($actor, 'sanctum')
            ->putJson('/api/users/'.$victim->id, [
                'email' => 'taken-over@example.com',
                'password' => 'new-password-123',
            ])
            ->assertForbidden();

        $this->assertSame($originalEmail, $victim->fresh()->email);
    }

    public function test_user_cannot_add_themselves_to_a_group(): void
    {
        $actor = $this->makeUser();
        $admin = Group::where('name', 'admin')->firstOrFail();

        $this->actingAs($actor, 'sanctum')
            ->putJson('/api/users/'.$actor->id, [
                'name' => 'Still Just Me',
                'group_list' => [$admin->id],
                'user_status_id' => UserStatus::ACTIVE,
            ])
            ->assertOk();

        $actor->refresh();
        $this->assertSame('Still Just Me', $actor->name);
        $this->assertFalse($actor->hasGroup('admin'));
    }

    public function test_user_cannot_change_their_own_status(): void
    {
        $actor = User::factory()->create(['user_status_id' => UserStatus::PENDING]);
        $actor->profile()->create([]);

        $this->actingAs($actor, 'sanctum')
            ->putJson('/api/users/'.$actor->id, ['user_status_id' => UserStatus::ACTIVE])
            ->assertOk();

        $this->assertSame(UserStatus::PENDING, (int) $actor->fresh()->user_status_id);
    }

    public function test_admin_can_update_another_users_groups(): void
    {
        $admin = $this->makeUser('admin');
        $target = $this->makeUser();
        $group = Group::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/users/'.$target->id, ['group_list' => [$group->id]])
            ->assertOk();

        $this->assertTrue($target->fresh()->groups->contains('id', $group->id));
    }

    public function test_store_ignores_a_requested_status(): void
    {
        $actor = $this->makeUser();

        $this->actingAs($actor, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'ZZ-Status-Probe',
                'email' => 'zz-status-probe@example.com',
                'password' => 'secret-pw-123',
                'user_status_id' => UserStatus::ACTIVE,
            ])
            ->assertOk();

        $this->assertSame(UserStatus::PENDING, (int) User::where('email', 'zz-status-probe@example.com')->value('user_status_id'));
    }
}
