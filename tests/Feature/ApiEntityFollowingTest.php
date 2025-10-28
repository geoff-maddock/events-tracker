<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEntityFollowingTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function testGuestCannotAccessFollowingEndpoint()
    {
        $this->expectException(\Illuminate\Auth\AuthenticationException::class);
        
        $this->withoutExceptionHandling()
            ->getJson('/api/entities/following');
    }

    public function testAuthenticatedUserCanGetFollowingEntities()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        $followedEntity = Entity::factory()->create([
            'name' => 'Followed Entity',
            'slug' => 'followed-entity',
            'entity_status_id' => 2, // Active status
        ]);

        $notFollowedEntity = Entity::factory()->create([
            'name' => 'Not Followed Entity',
            'slug' => 'not-followed-entity',
            'entity_status_id' => 2, // Active status
        ]);

        // Create a follow relationship - note: must refresh user to get fresh instance
        Follow::create([
            'user_id' => $user->id,
            'object_id' => $followedEntity->id,
            'object_type' => 'entity',
        ]);

        // Re-authenticate to ensure fresh user instance
        $user->refresh();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/entities/following');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Followed Entity')
            ->assertJsonPath('total', 1);
    }

    public function testFollowingEndpointRespectsFilters()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        $entity1 = Entity::factory()->create([
            'name' => 'Alpha Entity',
            'slug' => 'alpha-entity',
            'entity_status_id' => 2, // Active status
        ]);

        $entity2 = Entity::factory()->create([
            'name' => 'Beta Entity',
            'slug' => 'beta-entity',
            'entity_status_id' => 2, // Active status
        ]);

        // Follow both entities
        Follow::create([
            'user_id' => $user->id,
            'object_id' => $entity1->id,
            'object_type' => 'entity',
        ]);

        Follow::create([
            'user_id' => $user->id,
            'object_id' => $entity2->id,
            'object_type' => 'entity',
        ]);

        // Re-authenticate to ensure fresh user instance
        $user->refresh();
        $this->actingAs($user, 'sanctum');

        // Filter by name
        $response = $this->getJson('/api/entities/following?filters[name]=Alpha');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Alpha Entity')
            ->assertJsonPath('total', 1);
    }

    public function testFollowingEndpointReturnsPaginatedResults()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        // Create and follow multiple entities
        for ($i = 1; $i <= 10; $i++) {
            $entity = Entity::factory()->create([
                'name' => "Entity {$i}",
                'slug' => "entity-{$i}",
                'entity_status_id' => 2, // Active status
            ]);

            Follow::create([
                'user_id' => $user->id,
                'object_id' => $entity->id,
                'object_type' => 'entity',
            ]);
        }

        // Re-authenticate to ensure fresh user instance
        $user->refresh();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/entities/following?limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug']
                ],
                'current_page',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total'
            ]);

        $this->assertEquals(5, count($response->json('data')));
        $this->assertEquals(10, $response->json('total'));
    }
}
