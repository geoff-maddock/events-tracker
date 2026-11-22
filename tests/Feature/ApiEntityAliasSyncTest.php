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
use PHPUnit\Framework\Attributes\Test;

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

    #[Test]
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

    public function test_a_name_that_starts_with_digits_is_a_name_not_an_id(): void
    {
        $other = Alias::create(['name' => 'Someone Else']);
        $entity = Entity::factory()->create(['created_by' => $this->user->id, 'slug' => 'zz-alias-target']);

        // "{id} State" used to be coerced to the id in front of it
        $name = $other->id.' State';
        $this->putJson('/api/entities/'.$entity->slug, $this->payload(['slug' => $entity->slug, 'alias_list' => [$name]]))->assertOk();

        $this->assertSame([$name], $entity->fresh()->aliases->pluck('name')->all());
    }

    public function test_an_alias_name_that_exists_is_reused_whatever_its_case(): void
    {
        $alias = Alias::create(['name' => 'Dj Sample']);
        $entity = Entity::factory()->create(['created_by' => $this->user->id, 'slug' => 'zz-alias-target']);

        foreach (['store' => fn (array $list) => $this->postJson('/api/entities', $this->payload(['alias_list' => $list])),
            'update' => fn (array $list) => $this->putJson('/api/entities/'.$entity->slug, $this->payload(['slug' => $entity->slug, 'alias_list' => $list])),
        ] as $name => $request) {
            $request(['DJ SAMPLE', 'dj sample', 'Brand New Name'])->assertOk();

            $this->assertSame(1, Alias::whereRaw('LOWER(name) = ?', ['dj sample'])->count(), $name);
            $this->assertSame(1, Alias::where('name', 'Brand New Name')->count(), $name);
        }

        $this->assertEqualsCanonicalizing(
            [$alias->id, Alias::where('name', 'Brand New Name')->value('id')],
            $entity->fresh()->aliases->modelKeys()
        );
    }

    public function test_the_web_form_reuses_and_creates_aliases_the_same_way(): void
    {
        $alias = Alias::create(['name' => 'Dj Sample']);

        $this->actingAs($this->user, 'web')->post('/entities', $this->payload([
            'name' => 'ZZ Web Alias Entity', 'alias_list' => [(string) $alias->id, 'dj sample', '99 Problems'],
        ]))->assertSessionHasNoErrors();

        $entity = Entity::where('name', 'ZZ Web Alias Entity')->firstOrFail();
        $this->assertEqualsCanonicalizing(['Dj Sample', '99 Problems'], $entity->aliases->pluck('name')->all());
        $this->assertSame(1, Alias::whereRaw('LOWER(name) = ?', ['dj sample'])->count());
    }
}
