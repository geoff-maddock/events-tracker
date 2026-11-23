<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The web form and the API write an entity through the same service (#2407),
 * so what a write records does not depend on which one was used.
 */
class EntityWriterTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        // an admin, so the write is by someone other than the one who added it
        $this->editor = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->editor->groups()->attach(Group::where('name', 'admin')->firstOrFail()->id);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ZZ Writer Entity',
            'slug' => 'zz-writer-entity',
            'short' => 'A short blurb.',
            'description' => 'A longer description.',
            'entity_type_id' => EntityType::first()->id,
            'entity_status_id' => EntityStatus::ACTIVE,
        ], $overrides);
    }

    private function write(string $via, Entity $entity, array $changes): void
    {
        match ($via) {
            'web' => $this->actingAs($this->editor)->put('/entities/'.$entity->slug, $this->payload($changes))->assertSessionHasNoErrors(),
            'put' => $this->actingAs($this->editor, 'sanctum')->putJson('/api/entities/'.$entity->slug, $this->payload($changes))->assertOk(),
            'patch' => $this->actingAs($this->editor, 'sanctum')->patchJson('/api/entities/'.$entity->slug, $changes)->assertOk(),
            default => $this->fail('unknown write path '.$via),
        };
    }

    /** @return array<string, array{string}> */
    public static function writes(): array
    {
        return ['web form' => ['web'], 'API put' => ['put'], 'API patch' => ['patch']];
    }

    #[DataProvider('writes')]
    public function test_an_update_records_who_made_it_and_sets_the_lists(string $via): void
    {
        $entity = Entity::factory()->create([
            'slug' => 'zz-writer-entity', 'entity_status_id' => EntityStatus::ACTIVE,
            'created_by' => $this->owner->id, 'updated_by' => null,
        ]);
        $role = Role::firstOrFail();

        $this->write($via, $entity, [
            'name' => 'ZZ Renamed', 'role_list' => [$role->id], 'tag_list' => ['Zz Writer Tag'], 'alias_list' => ['Zz Writer Alias'],
        ]);

        $entity->refresh();
        $this->assertSame('ZZ Renamed', $entity->name);
        $this->assertSame($this->editor->id, $entity->updated_by, 'who last changed it');
        $this->assertSame($this->owner->id, $entity->created_by, 'who added it is unchanged');
        $this->assertSame([$role->id], $entity->roles()->pluck('roles.id')->all());
        $this->assertSame(['Zz Writer Tag'], $entity->tags()->pluck('name')->all());
        $this->assertSame(['Zz Writer Alias'], $entity->aliases()->pluck('name')->all());
        $this->assertSame(1, Activity::where('object_table', 'Entity')->where('object_id', $entity->id)->count(), 'one activity row per write');
    }

    public function test_a_new_entity_records_its_creator_on_both_paths(): void
    {
        $member = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($member)->post('/entities', $this->payload(['slug' => 'zz-web-new', 'name' => 'ZZ Web New']))->assertSessionHasNoErrors();
        $this->actingAs($member, 'sanctum')->postJson('/api/entities', $this->payload(['slug' => 'zz-api-new', 'name' => 'ZZ Api New']))->assertOk();

        foreach (['zz-web-new', 'zz-api-new'] as $slug) {
            $entity = Entity::where('slug', $slug)->firstOrFail();
            $this->assertSame($member->id, $entity->created_by, $slug);
            $this->assertSame($member->id, $entity->updated_by, $slug);
        }
    }

    public function test_the_web_update_says_which_tags_and_aliases_it_added(): void
    {
        $entity = Entity::factory()->create(['slug' => 'zz-writer-entity', 'entity_status_id' => EntityStatus::ACTIVE]);

        $this->actingAs($this->editor)->put('/entities/'.$entity->slug, $this->payload(['tag_list' => ['Zz New Tag'], 'alias_list' => ['Zz New Alias']]))
            ->assertSessionHasNoErrors();

        $message = session('flash_message')['message'] ?? '';
        $this->assertStringContainsString('Added tag Zz New Tag.', $message);
        $this->assertStringContainsString('Added alias Zz New Alias.', $message);
    }

    public function test_a_null_list_means_an_empty_one(): void
    {
        $nulls = ['tag_list' => null, 'alias_list' => null, 'role_list' => null];

        $this->actingAs($this->editor, 'sanctum')->postJson('/api/entities', $this->payload(['slug' => 'zz-null-lists', 'name' => 'ZZ Null Lists'] + $nulls))->assertOk();

        $entity = Entity::factory()->create(['slug' => 'zz-writer-entity', 'entity_status_id' => EntityStatus::ACTIVE]);
        $entity->roles()->attach(Role::firstOrFail()->id);
        $this->actingAs($this->editor, 'sanctum')->putJson('/api/entities/'.$entity->slug, $this->payload($nulls))->assertOk();

        $this->assertSame([], $entity->roles()->pluck('roles.id')->all(), 'a full replacement with no list clears it');
    }
}
