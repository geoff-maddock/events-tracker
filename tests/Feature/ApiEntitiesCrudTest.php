<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEntitiesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($this->user, 'sanctum');
    }

    private User $user;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ZZ-Test-Entity',
            'slug' => 'zz-test-entity-'.uniqid(),
            'short' => 'A short blurb.',
            'description' => 'A longer description for testing.',
            'entity_type_id' => EntityType::first()->id,
            'entity_status_id' => EntityStatus::first()->id,
        ], $overrides);
    }

    public function test_store_creates_entity_with_created_by_set_to_actor(): void
    {
        $payload = $this->validPayload(['name' => 'ZZ-Store-Entity']);

        $response = $this->postJson('/api/entities', $payload);

        $response->assertOk()->assertJsonFragment(['name' => 'ZZ-Store-Entity']);

        $entity = Entity::where('name', 'ZZ-Store-Entity')->first();
        $this->assertNotNull($entity);
        $this->assertSame($this->user->id, $entity->created_by);
    }

    public function test_store_rejects_invalid_payload(): void
    {
        $response = $this->postJson('/api/entities', ['name' => 'x']);

        $response->assertStatus(422);
    }

    public function test_update_replaces_entity_fields(): void
    {
        $entity = Entity::factory()->create([
            'created_by' => $this->user->id,
            'slug' => 'zz-update-target-'.uniqid(),
        ]);

        $payload = $this->validPayload([
            'name' => 'ZZ-Updated-Name',
            'slug' => $entity->slug,
        ]);

        $response = $this->putJson('/api/entities/'.$entity->slug, $payload);

        $response->assertOk();
        $this->assertSame('ZZ-Updated-Name', $entity->fresh()->name);
    }

    public function test_patch_partial_update_only_touches_supplied_fields(): void
    {
        $entity = Entity::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'ZZ-Original',
            'slug' => 'zz-patch-target-'.uniqid(),
        ]);
        $originalSlug = $entity->slug;

        $response = $this->patchJson('/api/entities/'.$entity->slug, [
            'short' => 'New short text only',
        ]);

        $response->assertOk();
        $fresh = $entity->fresh();
        $this->assertSame('New short text only', $fresh->short);
        $this->assertSame($originalSlug, $fresh->slug);
    }

    public function test_destroy_deletes_entity_when_actor_is_creator(): void
    {
        $entity = Entity::factory()->create([
            'created_by' => $this->user->id,
            'slug' => 'zz-destroy-mine-'.uniqid(),
        ]);

        $response = $this->deleteJson('/api/entities/'.$entity->slug);

        $response->assertStatus(204);
        $this->assertNull(Entity::find($entity->id));
    }

    public function test_destroy_refuses_when_actor_is_not_creator(): void
    {
        $other = User::factory()->create();
        $entity = Entity::factory()->create([
            'created_by' => $other->id,
            'slug' => 'zz-destroy-other-'.uniqid(),
        ]);

        // The controller's `unauthorized()` helper returns a 403 JSON for
        // request->ajax() and a 302 redirect otherwise; deleteJson does not
        // set X-Requested-With, so we accept both.
        $response = $this->deleteJson('/api/entities/'.$entity->slug);

        $this->assertContains($response->status(), [302, 401, 403]);
        $this->assertNotNull(Entity::find($entity->id));
    }

    public function test_add_link_attaches_link_to_entity(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->postJson('/api/entities/'.$entity->id.'/links', [
            'text' => 'Bandcamp',
            'url' => 'https://example.bandcamp.com',
        ]);

        $response->assertStatus(201);
        $this->assertGreaterThanOrEqual(1, $entity->fresh()->links()->count());
    }

    public function test_add_link_validates_required_fields(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->postJson('/api/entities/'.$entity->id.'/links', [
            'text' => 'x',
        ]);

        $response->assertStatus(422);
    }

    public function test_add_contact_attaches_contact_to_entity(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->postJson('/api/entities/'.$entity->id.'/contacts', [
            'name' => 'Booking',
            'type' => 'manager',
            'visibility_id' => \App\Models\Visibility::VISIBILITY_PUBLIC,
            'email' => 'booking@example.com',
        ]);

        $response->assertStatus(201);
        $this->assertGreaterThanOrEqual(1, $entity->fresh()->contacts()->count());
    }
}
