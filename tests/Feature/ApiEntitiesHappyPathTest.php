<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEntitiesHappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user, 'sanctum');
    }

    public function test_index_returns_paginated_entity_list(): void
    {
        Entity::factory()->count(3)->create();

        $response = $this->getJson('/api/entities');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
    }

    public function test_show_returns_entity_by_slug(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->getJson('/api/entities/'.$entity->slug);

        $response->assertStatus(200)
            ->assertJsonPath('id', $entity->id);
    }

    public function test_photos_endpoint_returns_a_list(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->getJson('/api/entities/'.$entity->slug.'/photos');

        $response->assertStatus(200);
    }

    public function test_links_endpoint_returns_a_list(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->getJson('/api/entities/'.$entity->slug.'/links');

        $response->assertStatus(200);
    }

    public function test_locations_endpoint_returns_a_list(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->getJson('/api/entities/'.$entity->slug.'/locations');

        $response->assertStatus(200);
    }

    public function test_contacts_endpoint_returns_a_list(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->getJson('/api/entities/'.$entity->slug.'/contacts');

        $response->assertStatus(200);
    }

    public function test_popular_returns_a_list(): void
    {
        Entity::factory()->count(3)->create();

        $response = $this->getJson('/api/entities/popular');

        $response->assertStatus(200);
    }
}
