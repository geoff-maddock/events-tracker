<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Group and permission membership grants privileges, so creating or editing
 * either is admin-only (destroy was already covered by #2138).
 */
class GroupPermissionWriteAuthorizationTest extends TestCase
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
        if ($group) {
            $user->assignGroup($group);
        }

        return $user->fresh();
    }

    private function makePermission(): Permission
    {
        $permission = new Permission();
        $permission->forceFill([
            'name' => 'zz_permission',
            'label' => 'ZZ Permission',
            'description' => 'ZZ',
            'level' => 1,
        ])->save();

        return $permission;
    }

    public function test_member_cannot_add_themselves_to_the_admin_group(): void
    {
        $member = $this->makeUser();
        $admin = Group::where('name', 'admin')->firstOrFail();
        $existingAdmin = $this->makeUser('admin');

        $this->actingAs($member)
            ->put(route('groups.update', $admin), [
                'name' => $admin->name,
                'label' => $admin->label,
                'level' => $admin->level,
                'user_list' => [$member->id],
            ])
            ->assertForbidden();

        $this->assertFalse($member->fresh()->hasGroup('admin'));
        $this->assertTrue($existingAdmin->fresh()->hasGroup('admin'));
    }

    public function test_member_cannot_create_a_group(): void
    {
        $member = $this->makeUser();

        $this->actingAs($member)
            ->post(route('groups.store'), [
                'name' => 'zz_group',
                'label' => 'ZZ Group',
                'level' => 1,
                'user_list' => [$member->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('groups', ['name' => 'zz_group']);
    }

    public function test_member_cannot_open_group_forms(): void
    {
        $member = $this->makeUser();
        $group = Group::factory()->create();

        $this->actingAs($member)->get(route('groups.create'))->assertForbidden();
        $this->actingAs($member)->get(route('groups.edit', $group))->assertForbidden();
    }

    public function test_guest_is_redirected_from_group_update(): void
    {
        $group = Group::factory()->create();

        $this->put(route('groups.update', $group), ['name' => 'renamed'])->assertRedirect();

        $this->assertDatabaseMissing('groups', ['id' => $group->id, 'name' => 'renamed']);
    }

    public function test_member_cannot_attach_a_permission_to_a_group(): void
    {
        $member = $this->makeUser();
        $permission = $this->makePermission();
        $group = Group::factory()->create();

        $this->actingAs($member)
            ->put(route('permissions.update', $permission), [
                'name' => $permission->name,
                'label' => $permission->label,
                'level' => 1,
                'group_list' => [$group->id],
            ])
            ->assertForbidden();

        $this->assertFalse($permission->fresh()->groups->contains('id', $group->id));
    }

    public function test_member_cannot_create_a_permission(): void
    {
        $member = $this->makeUser();

        $this->actingAs($member)
            ->post(route('permissions.store'), [
                'name' => 'zz_new_permission',
                'label' => 'ZZ New Permission',
                'level' => 1,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('permissions', ['name' => 'zz_new_permission']);
    }

    public function test_admin_can_update_group_membership(): void
    {
        $admin = $this->makeUser('admin');
        $member = $this->makeUser();
        $group = Group::factory()->create();

        $this->actingAs($admin)
            ->put(route('groups.update', $group), [
                'name' => $group->name,
                'label' => $group->label,
                'level' => $group->level,
                'user_list' => [$member->id],
            ])
            ->assertRedirect('groups');

        $this->assertTrue($member->fresh()->hasGroup($group->name));
    }
}
