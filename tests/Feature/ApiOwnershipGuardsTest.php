<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Event;
use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ownership guards on mutations that previously allowed any authenticated
 * user to alter another user's resource:
 *  - Api\EntitiesController::update / ::patch (destroy already guarded)
 *  - Api\ThreadsController::destroy
 *  - EventsController::createThread (was publicly reachable)
 *
 * Following the ApiEventsAuthBypassTest convention, the primary signal is
 * that data does not change for a non-owner, rather than the exact status.
 */
class ApiOwnershipGuardsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;
    private User $attacker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->attacker = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
    }

    private function entityPayload(Entity $entity, string $name): array
    {
        return [
            'name' => $name,
            'slug' => $entity->slug,
            'short' => 'short',
            'description' => 'description',
            'entity_type_id' => EntityType::first()->id,
            'entity_status_id' => EntityStatus::first()->id,
        ];
    }

    private function ownerEntity(): Entity
    {
        return Entity::factory()->create([
            'created_by' => $this->owner->id,
            'name' => 'Original ZZ',
            'slug' => 'zz-owner-entity-'.uniqid(),
        ]);
    }

    /** @test */
    public function entity_update_does_not_mutate_data_for_non_owner(): void
    {
        $entity = $this->ownerEntity();

        $this->actingAs($this->attacker, 'sanctum');
        $this->putJson('/api/entities/'.$entity->slug, $this->entityPayload($entity, 'Hijacked'));

        $this->assertSame('Original ZZ', $entity->fresh()->name);
    }

    /** @test */
    public function entity_patch_does_not_mutate_data_for_non_owner(): void
    {
        $entity = $this->ownerEntity();

        $this->actingAs($this->attacker, 'sanctum');
        $this->patchJson('/api/entities/'.$entity->slug, ['name' => 'Hijacked']);

        $this->assertSame('Original ZZ', $entity->fresh()->name);
    }

    /** @test */
    public function entity_update_succeeds_for_owner(): void
    {
        $entity = $this->ownerEntity();

        $this->actingAs($this->owner, 'sanctum');
        $this->putJson('/api/entities/'.$entity->slug, $this->entityPayload($entity, 'Renamed ZZ'))
            ->assertOk();

        $this->assertSame('Renamed ZZ', $entity->fresh()->name);
    }

    /** @test */
    public function api_thread_destroy_rejects_non_owner(): void
    {
        // authenticate as owner first so the Thread::creating hook stamps created_by
        $this->actingAs($this->owner);
        $thread = Thread::factory()->create();

        $this->actingAs($this->attacker, 'sanctum');
        $this->deleteJson('/api/threads/'.$thread->id)->assertStatus(403);

        $this->assertNotNull(Thread::find($thread->id), 'Thread should still exist.');
    }

    /** @test */
    public function api_thread_destroy_succeeds_for_owner(): void
    {
        $this->actingAs($this->owner);
        $thread = Thread::factory()->create();

        $this->actingAs($this->owner, 'sanctum');
        $this->deleteJson('/api/threads/'.$thread->id);

        $this->assertNull(Thread::find($thread->id), 'Owner should be able to delete their thread.');
    }

    /** @test */
    public function create_thread_is_blocked_for_non_owner(): void
    {
        $event = Event::factory()->create([
            'created_by' => $this->owner->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($this->attacker);
        $this->get('/events/create-thread?id='.$event->id);

        $this->assertSame(0, Thread::where('event_id', $event->id)->count());
    }

    /** @test */
    public function create_thread_is_blocked_for_guest(): void
    {
        $event = Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $this->get('/events/create-thread?id='.$event->id)
            ->assertRedirect('/login');

        $this->assertSame(0, Thread::where('event_id', $event->id)->count());
    }

    /** @test */
    public function create_thread_succeeds_for_owner(): void
    {
        $event = Event::factory()->create([
            'created_by' => $this->owner->id,
            'visibility_id' => Visibility::VISIBILITY_PUBLIC,
        ]);

        $this->actingAs($this->owner);
        $this->get('/events/create-thread?id='.$event->id);

        $this->assertSame(1, Thread::where('event_id', $event->id)->count());
    }
}
