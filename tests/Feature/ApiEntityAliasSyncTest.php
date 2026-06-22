<?php

namespace Tests\Feature;

use App\Models\Alias;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the inverted alias-existence condition in
 * EntitiesController::store (and its API twin).
 *
 * The original condition created a brand-new alias whenever the supplied
 * id ALREADY existed (and tried to attach a raw id-string as an alias id
 * when it did NOT). The result was duplicate alias rows and the wrong
 * alias being attached. The fix flips the condition so an existing alias
 * id is attached as-is and only genuinely new names create rows.
 */
class ApiEntityAliasSyncTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($this->user, 'sanctum');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ZZ-Alias-Entity',
            'slug' => 'zz-alias-entity-'.uniqid(),
            'short' => 'short',
            'description' => 'description',
            'entity_type_id' => EntityType::first()->id,
            'entity_status_id' => EntityStatus::first()->id,
        ], $overrides);
    }

    /** @test */
    public function storing_with_an_existing_alias_id_attaches_it_without_duplicating(): void
    {
        $alias = Alias::create(['name' => 'The Original Alias']);
        $aliasCountBefore = Alias::count();

        $response = $this->postJson('/api/entities', $this->payload([
            'alias_list' => [$alias->id],
        ]));

        $response->assertOk();

        $entity = Entity::where('name', 'ZZ-Alias-Entity')->firstOrFail();

        // The existing alias is attached...
        $this->assertTrue(
            $entity->aliases->contains('id', $alias->id),
            'Existing alias should be attached to the entity.'
        );
        // ...and no duplicate alias row was created.
        $this->assertSame($aliasCountBefore, Alias::count());
    }
}
