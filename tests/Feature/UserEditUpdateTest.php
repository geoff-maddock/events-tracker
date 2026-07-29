<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEditUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $admin->assignGroup('admin');

        return $admin->fresh();
    }

    /** @test */
    public function edit_form_shows_populated_name_and_email_fields(): void
    {
        $this->withExceptionHandling();

        $admin = $this->makeAdmin();
        $target = User::factory()->create([
            'name' => 'Target Person',
            'email' => 'target@example.com',
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        $response = $this->actingAs($admin)->get(route('users.edit', $target->id));
        $response->assertStatus(200);

        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/<input[^>]*name="name"[^>]*value="Target Person"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<input[^>]*name="email"[^>]*value="target@example\.com"/',
            $html
        );
    }

    /** @test */
    public function admin_can_update_another_users_name_and_email(): void
    {
        $this->withExceptionHandling();

        $admin = $this->makeAdmin();
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $response = $this->actingAs($admin)->patch(route('users.update', $target->id), [
            'name' => 'Renamed Person',
            'email' => 'renamed@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $target->refresh();
        $this->assertSame('Renamed Person', $target->name);
        $this->assertSame('renamed@example.com', $target->email);
    }

    /** @test */
    public function update_rejects_missing_name_and_invalid_email(): void
    {
        $this->withExceptionHandling();

        $admin = $this->makeAdmin();
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $originalEmail = $target->email;

        $response = $this->actingAs($admin)->patch(route('users.update', $target->id), [
            'name' => '',
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['name', 'email']);
        $this->assertSame($originalEmail, $target->fresh()->email);
    }

    /** @test */
    public function update_rejects_email_already_used_by_another_user(): void
    {
        $this->withExceptionHandling();

        $admin = $this->makeAdmin();
        $other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $response = $this->actingAs($admin)->patch(route('users.update', $target->id), [
            'name' => $target->name,
            'email' => $other->email,
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function user_can_update_own_profile_keeping_own_email(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $response = $this->actingAs($user)->patch(route('users.update', $user->id), [
            'name' => 'My New Name',
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('My New Name', $user->fresh()->name);
    }

    /** @test */
    public function regular_user_cannot_view_edit_form_for_another_user(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)
            ->get(route('users.edit', $target->id))
            ->assertStatus(403);
    }

    /** @test */
    public function regular_user_cannot_update_another_user(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $originalName = $target->name;

        $this->actingAs($user)
            ->patch(route('users.update', $target->id), [
                'name' => 'Hacked Name',
                'email' => 'hacked@example.com',
            ])
            ->assertStatus(403);

        $this->assertSame($originalName, $target->fresh()->name);
    }

    /** @test */
    public function regular_user_cannot_delete_another_user(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($user)
            ->delete(route('users.destroy', $target->id))
            ->assertStatus(403);

        $this->assertNotNull(User::find($target->id));
    }

    /** @test */
    public function regular_user_cannot_grant_themselves_groups_or_change_status(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $adminGroup = Group::where('name', 'admin')->firstOrFail();

        $response = $this->actingAs($user)->patch(route('users.update', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'user_status_id' => UserStatus::SUSPENDED,
            'group_list' => [$adminGroup->id],
        ]);

        $response->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame(UserStatus::ACTIVE, $user->user_status_id);
        $this->assertFalse($user->hasGroup('admin'));
    }

    /** @test */
    public function admin_can_still_change_status_and_groups(): void
    {
        $this->withExceptionHandling();

        $admin = $this->makeAdmin();
        $target = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $adminGroup = Group::where('name', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->patch(route('users.update', $target->id), [
            'name' => $target->name,
            'email' => $target->email,
            'user_status_id' => UserStatus::SUSPENDED,
            'group_list' => [$adminGroup->id],
        ]);

        $response->assertSessionHasNoErrors();
        $target->refresh();
        $this->assertSame(UserStatus::SUSPENDED, $target->user_status_id);
        $this->assertTrue($target->hasGroup('admin'));
    }
}
