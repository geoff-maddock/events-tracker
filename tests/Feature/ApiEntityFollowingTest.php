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
        $response = $this->getJson('/api/entities/following');

        $response->assertStatus(401);
    }

    public function testAuthenticatedUserCanGetFollowingEntities()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        $followedEntity = Entity::factory()->create([
            'name' => 'Followed Entity',
            'slug' => 'followed-entity',
        ]);

        $notFollowedEntity = Entity::factory()->create([
            'name' => 'Not Followed Entity',
            'slug' => 'not-followed-entity',
        ]);

        // Create a follow relationship
        Follow::create([
            'user_id' => $user->id,
            'object_id' => $followedEntity->id,
            'object_type' => 'entity',
        ]);

        $response = $this->getJson('/api/entities/following');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Followed Entity'])
            ->assertJsonMissing(['name' => 'Not Followed Entity']);
    }

    public function testFollowingEndpointRespectsFilters()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        $entity1 = Entity::factory()->create([
            'name' => 'Alpha Entity',
            'slug' => 'alpha-entity',
        ]);

        $entity2 = Entity::factory()->create([
            'name' => 'Beta Entity',
            'slug' => 'beta-entity',
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

        // Filter by name
        $response = $this->getJson('/api/entities/following?filters[name]=Alpha');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Alpha Entity'])
            ->assertJsonMissing(['name' => 'Beta Entity']);
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
            ]);

            Follow::create([
                'user_id' => $user->id,
                'object_id' => $entity->id,
                'object_type' => 'entity',
            ]);
        }

        $response = $this->getJson('/api/entities/following?limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug']
                ],
                'links',
                'meta' => ['total', 'per_page', 'current_page']
            ]);

        $this->assertEquals(5, count($response->json('data')));
    }
}
