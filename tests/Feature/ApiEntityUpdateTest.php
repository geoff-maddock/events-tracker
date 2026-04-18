<?php

namespace Tests\Feature;

use App\Models\Alias;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEntityUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function authenticatedUser(): User
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        return $user;
    }

    private function makeEntityWithRelations(): array
    {
        $entity = Entity::factory()->create([
            'slug' => 'fixture-entity',
            'instagram_username' => 'original_ig',
            'twitter_username' => 'original_tw',
            'facebook_username' => 'original_fb',
        ]);

        $tag = Tag::factory()->create();
        $alias = Alias::create(['name' => 'Original Alias']);
        $role = Role::factory()->create();

        $entity->tags()->attach($tag->id);
        $entity->aliases()->attach($alias->id);
        $entity->roles()->attach($role->id);

        return compact('entity', 'tag', 'alias', 'role');
    }

    private function basePayload(Entity $entity): array
    {
        return [
            'name' => $entity->name,
            'slug' => $entity->slug,
            'short' => $entity->short,
            'description' => $entity->description,
            'entity_type_id' => $entity->entity_type_id,
            'entity_status_id' => $entity->entity_status_id,
        ];
    }

    public function test_put_replaces_entire_resource_including_relations(): void
    {
        $this->authenticatedUser();
        ['entity' => $entity] = $this->makeEntityWithRelations();

        $newType = EntityType::query()->where('id', '!=', $entity->entity_type_id)->first()
            ?? EntityType::query()->first();
        $newStatus = EntityStatus::query()->where('id', '!=', $entity->entity_status_id)->first()
            ?? EntityStatus::query()->first();
        $newTag = Tag::factory()->create();
        $newRole = Role::factory()->create();

        $payload = [
            'name' => 'Replaced Name',
            'slug' => 'replaced-slug',
            'short' => 'Replaced short blurb',
            'description' => 'Replaced description body.',
            'entity_type_id' => $newType->id,
            'entity_status_id' => $newStatus->id,
            'instagram_username' => 'replaced_ig',
            'twitter_username' => 'replaced_tw',
            'facebook_username' => 'replaced_fb',
            'tag_list' => [$newTag->id],
            'role_list' => [$newRole->id],
            'alias_list' => ['Brand New Alias'],
        ];

        $response = $this->putJson("/api/entities/{$entity->id}", $payload);
        $response->assertOk();

        $entity->refresh();
        $this->assertSame('Replaced Name', $entity->name);
        $this->assertSame('replaced-slug', $entity->slug);
        $this->assertSame('replaced_ig', $entity->instagram_username);
        $this->assertEqualsCanonicalizing([$newTag->id], $entity->tags()->pluck('tags.id')->all());
        $this->assertEqualsCanonicalizing([$newRole->id], $entity->roles()->pluck('roles.id')->all());
        $this->assertSame(1, $entity->aliases()->where('name', 'Brand New Alias')->count());
    }

    public function test_put_clears_optional_scalars_when_omitted(): void
    {
        $this->authenticatedUser();
        ['entity' => $entity] = $this->makeEntityWithRelations();

        // PUT with only the required scalars; optional username fields are absent.
        $response = $this->putJson("/api/entities/{$entity->id}", $this->basePayload($entity));
        $response->assertOk();

        $entity->refresh();
        $this->assertNull($entity->instagram_username);
        $this->assertNull($entity->twitter_username);
        $this->assertNull($entity->facebook_username);
    }

    public function test_put_detaches_relations_when_lists_omitted(): void
    {
        $this->authenticatedUser();
        ['entity' => $entity] = $this->makeEntityWithRelations();

        $response = $this->putJson("/api/entities/{$entity->id}", $this->basePayload($entity));
        $response->assertOk();

        $entity->refresh();
        $this->assertSame(0, $entity->tags()->count());
        $this->assertSame(0, $entity->aliases()->count());
        $this->assertSame(0, $entity->roles()->count());
    }

    public function test_patch_updates_only_supplied_scalar_field(): void
    {
        $this->authenticatedUser();
        ['entity' => $entity, 'tag' => $tag, 'alias' => $alias, 'role' => $role] = $this->makeEntityWithRelations();

        $originalName = $entity->name;
        $originalShort = $entity->short;
        $originalTwitter = $entity->twitter_username;
        $originalFacebook = $entity->facebook_username;

        $response = $this->patchJson("/api/entities/{$entity->id}", [
            'instagram_username' => 'boom_concepts',
        ]);
        $response->assertOk();

        $entity->refresh();
        $this->assertSame('boom_concepts', $entity->instagram_username);
        $this->assertSame($originalName, $entity->name);
        $this->assertSame($originalShort, $entity->short);
        $this->assertSame($originalTwitter, $entity->twitter_username);
        $this->assertSame($originalFacebook, $entity->facebook_username);

        // Relations untouched.
        $this->assertEqualsCanonicalizing([$tag->id], $entity->tags()->pluck('tags.id')->all());
        $this->assertEqualsCanonicalizing([$alias->id], $entity->aliases()->pluck('aliases.id')->all());
        $this->assertEqualsCanonicalizing([$role->id], $entity->roles()->pluck('roles.id')->all());
    }

    public function test_patch_with_only_tag_list_preserves_other_relations_and_scalars(): void
    {
        $this->authenticatedUser();
        ['entity' => $entity, 'alias' => $alias, 'role' => $role] = $this->makeEntityWithRelations();

        $originalName = $entity->name;
        $originalInstagram = $entity->instagram_username;
        $newTag = Tag::factory()->create();

        $response = $this->patchJson("/api/entities/{$entity->id}", [
            'tag_list' => [$newTag->id],
        ]);
        $response->assertOk();

        $entity->refresh();
        $this->assertEqualsCanonicalizing([$newTag->id], $entity->tags()->pluck('tags.id')->all());
        $this->assertEqualsCanonicalizing([$alias->id], $entity->aliases()->pluck('aliases.id')->all());
        $this->assertEqualsCanonicalizing([$role->id], $entity->roles()->pluck('roles.id')->all());
        $this->assertSame($originalName, $entity->name);
        $this->assertSame($originalInstagram, $entity->instagram_username);
    }

    public function test_patch_accepts_partial_body_without_required_fields(): void
    {
        $this->authenticatedUser();
        ['entity' => $entity] = $this->makeEntityWithRelations();

        // No name/slug/short/description/entity_type_id/entity_status_id supplied.
        $response = $this->patchJson("/api/entities/{$entity->id}", [
            'instagram_username' => 'just_this_one',
        ]);

        $response->assertOk();
        $this->assertSame('just_this_one', $entity->fresh()->instagram_username);
    }

    public function test_put_requires_full_payload(): void
    {
        $this->authenticatedUser();
        ['entity' => $entity] = $this->makeEntityWithRelations();

        // PUT with only one field — required validation should reject it.
        $response = $this->putJson("/api/entities/{$entity->id}", [
            'instagram_username' => 'partial_only',
        ]);

        $response->assertStatus(422);
    }
}
