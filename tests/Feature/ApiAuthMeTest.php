<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthMeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function it_returns_user_data_with_roles_and_permissions()
    {
        // Create a user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'user_status_id' => 2, // Active status
        ]);

        // Create a group (role) with permissions
        $group = Group::create([
            'name' => 'admin',
            'label' => 'Admin',
            'level' => 100,
            'description' => 'Administrator group',
        ]);

        // Create permissions
        $permission1 = Permission::create([
            'name' => 'edit_event',
            'label' => 'Edit Events',
            'description' => 'Can edit events',
            'level' => 10,
        ]);

        $permission2 = Permission::create([
            'name' => 'edit_entity',
            'label' => 'Edit Entities',
            'description' => 'Can edit entities',
            'level' => 10,
        ]);

        // Assign permissions to group
        $group->permissions()->attach([$permission1->id, $permission2->id]);

        // Assign group to user
        $user->groups()->attach($group->id);

        // Authenticate as the user
        $this->actingAs($user, 'sanctum');

        // Make request to /auth/me endpoint
        $response = $this->getJson('/api/auth/me');

        // Assert the response structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'roles' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                        ],
                    ],
                    'permissions' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                        ],
                    ],
                ],
            ]);

        // Assert specific role data
        $response->assertJsonPath('data.roles.0.id', $group->id)
            ->assertJsonPath('data.roles.0.name', 'Admin')
            ->assertJsonPath('data.roles.0.slug', 'admin');

        // Assert specific permission data
        $response->assertJsonCount(2, 'data.permissions')
            ->assertJsonFragment([
                'id' => $permission1->id,
                'name' => 'Edit Events',
                'slug' => 'edit_event',
            ])
            ->assertJsonFragment([
                'id' => $permission2->id,
                'name' => 'Edit Entities',
                'slug' => 'edit_entity',
            ]);
    }

    /** @test */
    public function it_returns_empty_roles_and_permissions_for_user_without_groups()
    {
        // Create a user without any groups
        $user = User::factory()->create([
            'name' => 'Basic User',
            'email' => 'basic@example.com',
            'user_status_id' => 2, // Active status
        ]);

        // Authenticate as the user
        $this->actingAs($user, 'sanctum');

        // Make request to /auth/me endpoint
        $response = $this->getJson('/api/auth/me');

        // Assert the response has empty roles and permissions
        $response->assertStatus(200)
            ->assertJsonPath('data.roles', [])
            ->assertJsonPath('data.permissions', []);
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Make request without authentication
        $response = $this->getJson('/api/auth/me');

        // Assert unauthorized response
        $response->assertStatus(401);
    }

    /** @test */
    public function it_deduplicates_permissions_from_multiple_groups()
    {
        // Create a user
        $user = User::factory()->create([
            'name' => 'Multi Group User',
            'email' => 'multigroup@example.com',
            'user_status_id' => 2, // Active status
        ]);

        // Create two groups
        $group1 = Group::create([
            'name' => 'group1',
            'label' => 'Group 1',
            'level' => 50,
            'description' => 'First group',
        ]);

        $group2 = Group::create([
            'name' => 'group2',
            'label' => 'Group 2',
            'level' => 60,
            'description' => 'Second group',
        ]);

        // Create a shared permission
        $sharedPermission = Permission::create([
            'name' => 'edit_event',
            'label' => 'Edit Events',
            'description' => 'Can edit events',
            'level' => 10,
        ]);

        // Create a unique permission for group 2
        $uniquePermission = Permission::create([
            'name' => 'delete_event',
            'label' => 'Delete Events',
            'description' => 'Can delete events',
            'level' => 20,
        ]);

        // Assign the shared permission to both groups
        $group1->permissions()->attach($sharedPermission->id);
        $group2->permissions()->attach([$sharedPermission->id, $uniquePermission->id]);

        // Assign both groups to user
        $user->groups()->attach([$group1->id, $group2->id]);

        // Authenticate as the user
        $this->actingAs($user, 'sanctum');

        // Make request to /auth/me endpoint
        $response = $this->getJson('/api/auth/me');

        // Assert that permissions are deduplicated (should only see edit_event once)
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.permissions'); // Should have 2 unique permissions

        // Verify the shared permission appears only once
        $permissions = $response->json('data.permissions');
        $editEventCount = collect($permissions)->where('slug', 'edit_event')->count();
        $this->assertEquals(1, $editEventCount, 'Shared permission should appear only once');
    }
}
