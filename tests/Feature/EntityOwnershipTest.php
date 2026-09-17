<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Link;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Ownership lives in entity_owners, not created_by, so it can be transferred
 * away from the person who first added an entity (#2147).
 */
class EntityOwnershipTest extends TestCase
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

        return $user;
    }

    private function updatePayload(Entity $entity, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Renamed Entity',
            'slug' => 'renamed-entity-'.$entity->id,
            'short' => 'Short description',
            'description' => 'Longer description',
            'entity_type_id' => EntityType::first()->id,
            'entity_status_id' => EntityStatus::first()->id,
        ], $overrides);
    }

    /**
     * An entity created by $creator whose ownership was then moved to $newOwner.
     */
    private function transferredEntity(User $creator, User $newOwner): Entity
    {
        $entity = Entity::factory()->create(['created_by' => $creator->id, 'name' => 'Original Name']);
        $entity->syncOwners([$newOwner->id], $this->makeUser('admin'));

        return $entity;
    }

    public function test_creating_an_entity_makes_the_creator_its_owner(): void
    {
        $creator = $this->makeUser();

        $entity = Entity::factory()->create(['created_by' => $creator->id]);

        $this->assertTrue($entity->isOwnedBy($creator));
        $this->assertTrue($creator->ownedEntities()->whereKey($entity->id)->exists());
    }

    public function test_an_added_owner_who_did_not_create_the_entity_can_edit_it(): void
    {
        $entity = Entity::factory()->create(['created_by' => $this->makeUser()->id]);
        $coOwner = $this->makeUser();
        $entity->owners()->attach($coOwner->id);

        $this->actingAs($coOwner)->get(route('entities.edit', $entity))->assertOk();
        $this->actingAs($coOwner)
            ->put(route('entities.update', $entity), $this->updatePayload($entity))
            ->assertRedirect(route('entities.show', $entity->fresh()));

        $this->assertSame('Renamed Entity', $entity->fresh()->name);
    }

    public function test_the_original_creator_loses_all_control_after_a_transfer(): void
    {
        $creator = $this->makeUser();
        $entity = $this->transferredEntity($creator, $this->makeUser());
        $link = Link::factory()->create(['text' => 'Original link']);
        $entity->links()->attach($link->id);

        $this->actingAs($creator);

        // web
        $this->get(route('entities.edit', $entity))->assertRedirect(route('entities.show', $entity));
        $this->put(route('entities.update', $entity), $this->updatePayload($entity))->assertRedirect('/');
        $this->delete(route('entities.destroy', $entity))->assertForbidden();
        $this->post(route('entities.links.store', $entity), ['text' => 'Spam link', 'url' => 'https://spam.example'])
            ->assertForbidden();
        $this->put(route('entities.links.update', [$entity, $link]), ['text' => 'Hijacked', 'url' => 'https://spam.example'])
            ->assertForbidden();

        // api
        $this->actingAs($creator, 'sanctum');
        $this->putJson('/api/entities/'.$entity->slug, $this->updatePayload($entity), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertForbidden();
        $this->postJson('/api/entities/'.$entity->id.'/links', ['text' => 'Spam link', 'url' => 'https://spam.example'])
            ->assertForbidden();
        $this->putJson('/api/entities/'.$entity->id.'/links/'.$link->id, ['text' => 'Hijacked', 'url' => 'https://spam.example'])
            ->assertForbidden();
        $this->deleteJson('/api/entities/'.$entity->id.'/links/'.$link->id)->assertForbidden();

        $this->assertSame('Original Name', $entity->fresh()->name);
        $this->assertDatabaseHas('links', ['id' => $link->id, 'text' => 'Original link']);
        $this->assertDatabaseMissing('links', ['text' => 'Spam link']);
    }

    public function test_the_new_owner_has_full_control_after_a_transfer(): void
    {
        $newOwner = $this->makeUser();
        $entity = $this->transferredEntity($this->makeUser(), $newOwner);

        $this->actingAs($newOwner)->get(route('entities.edit', $entity))->assertOk();
        $this->actingAs($newOwner)
            ->put(route('entities.update', $entity), $this->updatePayload($entity))
            ->assertRedirect();
        $this->assertSame('Renamed Entity', $entity->fresh()->name);

        $this->actingAs($newOwner)->delete(route('entities.destroy', $entity->fresh()))->assertRedirect('entities');
        $this->assertDatabaseMissing('entities', ['id' => $entity->id]);
    }

    public function test_admin_can_edit_an_entity_they_do_not_own_and_sees_the_edit_link(): void
    {
        $entity = Entity::factory()->create(['created_by' => $this->makeUser()->id]);
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('entities.show', $entity))
            ->assertOk()
            ->assertSee('Edit Entity');
        $this->actingAs($admin)
            ->put(route('entities.update', $entity), $this->updatePayload($entity))
            ->assertRedirect();

        $this->assertSame('Renamed Entity', $entity->fresh()->name);
    }

    public function test_admin_can_replace_owners_from_the_entity_form(): void
    {
        $creator = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $creator->id]);
        $newOwner = $this->makeUser();
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->put(route('entities.update', $entity), $this->updatePayload($entity, [
                'manage_owners' => 1,
                'owner_list' => [$newOwner->id],
            ]))
            ->assertRedirect();

        $entity = $entity->fresh();
        $this->assertFalse($entity->isOwnedBy($creator));
        $this->assertTrue($entity->isOwnedBy($newOwner));
        $this->assertSame($admin->id, (int) $entity->owners()->first()->pivot->granted_by);
        $this->assertSame($creator->id, (int) $entity->created_by);
    }

    public function test_owner_cannot_change_owners_or_created_by(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);
        $accomplice = $this->makeUser();

        $this->actingAs($owner)
            ->put(route('entities.update', $entity), $this->updatePayload($entity, [
                'manage_owners' => 1,
                'owner_list' => [$accomplice->id],
                'created_by' => $accomplice->id,
            ]))
            ->assertRedirect();

        $entity = $entity->fresh();
        $this->assertTrue($entity->isOwnedBy($owner));
        $this->assertFalse($entity->isOwnedBy($accomplice));
        $this->assertSame($owner->id, (int) $entity->created_by);
    }

    public function test_saving_the_form_without_the_owners_panel_keeps_the_owners(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($this->makeUser('admin'))
            ->put(route('entities.update', $entity), $this->updatePayload($entity))
            ->assertRedirect();

        $this->assertTrue($entity->fresh()->isOwnedBy($owner));
    }

    public function test_migration_backfills_owners_from_created_by(): void
    {
        $creator = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $creator->id]);
        $orphan = Entity::factory()->create(['created_by' => $creator->id]);

        $migration = require database_path('migrations/2026_09_17_000000_create_entity_owners_table.php');
        $migration->down();
        // a creator that no longer exists must not break the backfill
        DB::table('entities')->where('id', $orphan->id)->update(['created_by' => 999999]);
        $migration->up();

        $this->assertDatabaseHas('entity_owners', ['entity_id' => $entity->id, 'user_id' => $creator->id]);
        $this->assertDatabaseMissing('entity_owners', ['entity_id' => $orphan->id]);
    }
}
