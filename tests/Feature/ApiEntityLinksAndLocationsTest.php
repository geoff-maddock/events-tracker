<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\LocationType;
use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class ApiEntityLinksAndLocationsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_authenticated_user_can_add_link_to_entity(): void
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $user->user_status_id = 1; // Assuming 1 is the ID for active status
        $this->actingAs($user, 'sanctum');

        $payload = [
            'text' => 'Example',
            'url' => 'https://example.com',
            'title' => 'Example link',
            'is_primary' => true,
        ];

        $response = $this->postJson("/api/entities/{$entity->id}/links", $payload);
        $response->assertStatus(201)->assertJsonFragment(['url' => 'https://example.com']);

        $this->assertDatabaseHas('entity_link', [
            'entity_id' => $entity->id,
            'link_id' => $response->json('id'),
        ]);
    }

    public function test_authenticated_user_can_add_location_to_entity(): void
    {
        $user = User::factory()->create();
        $entity = Entity::factory()->create();
        $visibility = Visibility::factory()->create();
        $locationType = LocationType::factory()->create();
        $user->user_status_id = 1; // Assuming 1 is the ID for active status
        $this->actingAs($user, 'sanctum');

        $payload = [
            'name' => 'New Location',
            'slug' => 'new-location',
            'city' => 'City',
            'visibility_id' => $visibility->id,
            'location_type_id' => $locationType->id,
        ];

        $response = $this->postJson("/api/entities/{$entity->id}/locations", $payload);
        $response->assertStatus(201)->assertJsonFragment(['slug' => 'new-location']);

        $this->assertDatabaseHas('locations', [
            'slug' => 'new-location',
            'entity_id' => $entity->id,
        ]);
    }

    public function test_guest_cannot_add_link_or_location_to_entity(): void
    {
        $entity = Entity::factory()->create();

        // Re-enable exception handling for this test
        $this->withExceptionHandling();
 
        $linkResponse = $this->postJson("/api/entities/{$entity->id}/links", [
            'text' => 'Example',
            'url' => 'https://example.com',
        ]);
        $linkResponse->assertStatus(401);

        $locationResponse = $this->postJson("/api/entities/{$entity->id}/locations", [
            'name' => 'Loc',
            'slug' => 'loc',
            'city' => 'Town',
            'visibility_id' => 1,
            'location_type_id' => 1,
        ]);
        $locationResponse->assertStatus(401);
    }
}

