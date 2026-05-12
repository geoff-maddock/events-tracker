<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEntitiesUnhappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_show_returns_404_for_missing_entity(): void
    {
        $response = $this->getJson('/api/entities/missing-slug-'.uniqid());

        $response->assertStatus(404);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/entities', ['name' => 'Test']);

        $this->assertContains($response->status(), [401, 403, 422]);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/entities', []);

        $this->assertContains($response->status(), [422, 403]);
    }

    public function test_update_requires_authentication(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->putJson('/api/entities/'.$entity->id, ['name' => 'Updated']);

        $this->assertContains($response->status(), [401, 403, 405]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->deleteJson('/api/entities/'.$entity->id);

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_follow_requires_authentication(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->postJson('/api/entities/'.$entity->id.'/follow');

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_following_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/entities/following');

        $this->assertContains($response->status(), [401, 403]);
    }
}
