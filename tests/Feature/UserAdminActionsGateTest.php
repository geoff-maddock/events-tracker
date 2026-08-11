<?php

namespace Tests\Feature;

use App\Mail\UserActivation;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The user admin actions (activate / suspend / delete / reminder) are exposed
 * as plain GET routes and were previously reachable by any signed-in user —
 * the views gate the buttons behind @can('grant_access'), but the routes
 * themselves carried no middleware. These tests pin the gate.
 *
 * `weekly` is deliberately different: the profile page offers "Send Weekly
 * Update" to the account owner, so it is owner-or-admin rather than
 * admin-only.
 */
class UserAdminActionsGateTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * UserFactory picks a random user_status_id, and the global CheckBanned
     * middleware redirects anyone who is not active — which would mask the
     * gate under test. Acting users must therefore be pinned to ACTIVE.
     */
    private function activeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge(
            ['user_status_id' => UserStatus::ACTIVE],
            $attributes
        ));
    }

    private function admin(): User
    {
        $admin = $this->activeUser();
        $admin->assignGroup('admin');

        return $admin;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function adminOnlyActionProvider(): array
    {
        return [
            'activate' => ['activate'],
            'suspend' => ['suspend'],
            'delete' => ['delete'],
            'reminder' => ['reminder'],
        ];
    }

    /**
     * @dataProvider adminOnlyActionProvider
     */
    public function test_guests_cannot_reach_the_user_admin_actions(string $action): void
    {
        $target = User::factory()->create();

        $this->withExceptionHandling()
            ->get('/users/'.$target->id.'/'.$action)
            ->assertRedirect('/login');
    }

    /**
     * @dataProvider adminOnlyActionProvider
     */
    public function test_non_admins_cannot_reach_the_user_admin_actions(string $action): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->activeUser())
            ->withExceptionHandling()
            ->get('/users/'.$target->id.'/'.$action)
            ->assertStatus(403);
    }

    public function test_a_non_admin_cannot_activate_another_account(): void
    {
        Mail::fake();

        $target = User::factory()->create(['user_status_id' => UserStatus::PENDING]);

        $this->actingAs($this->activeUser())
            ->withExceptionHandling()
            ->get('/users/'.$target->id.'/activate')
            ->assertStatus(403);

        $this->assertSame(UserStatus::PENDING, $target->fresh()->user_status_id);
        Mail::assertNothingSent();
    }

    public function test_an_admin_can_activate_an_account_and_the_user_is_emailed(): void
    {
        Mail::fake();

        $admin = $this->admin();

        $target = User::factory()->create([
            'user_status_id' => UserStatus::PENDING,
            'email_verified_at' => null,
        ]);

        $this->actingAs($admin)
            ->withExceptionHandling()
            ->get('/users/'.$target->id.'/activate')
            ->assertRedirect();

        $target = $target->fresh();
        $this->assertSame(UserStatus::ACTIVE, $target->user_status_id);
        $this->assertNotNull($target->email_verified_at);

        Mail::assertSent(UserActivation::class, function (UserActivation $mail) use ($target) {
            return $mail->hasTo($target->email);
        });
    }

    public function test_a_non_admin_cannot_send_another_users_weekly_update(): void
    {
        Mail::fake();

        $target = User::factory()->create();

        $this->actingAs($this->activeUser())
            ->withExceptionHandling()
            ->get('/users/'.$target->id.'/weekly')
            ->assertStatus(403);

        Mail::assertNothingSent();
    }

    public function test_a_user_may_send_their_own_weekly_update(): void
    {
        Mail::fake();

        $user = $this->activeUser();
        Profile::factory()->create(['user_id' => $user->id, 'setting_weekly_update' => 1]);

        $this->actingAs($user)
            ->withExceptionHandling()
            ->get('/users/'.$user->id.'/weekly')
            ->assertStatus(302);
    }

    public function test_guests_cannot_reach_the_weekly_update_action(): void
    {
        $target = User::factory()->create();

        $this->withExceptionHandling()
            ->get('/users/'.$target->id.'/weekly')
            ->assertRedirect('/login');
    }
}
