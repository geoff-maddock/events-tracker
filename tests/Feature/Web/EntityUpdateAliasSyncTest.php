<?php

namespace Tests\Feature\Web;

use App\Models\Alias;
use App\Models\Entity;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for EVENTREPO-XB.
 *
 * EntitiesController::update looked up submitted alias values with
 * Alias::find($alias) without first checking is_numeric($alias) (unlike
 * the tag loop directly above it and the store() method). When a user
 * submitted a free-text alias name that begins with digits (e.g. "7D"),
 * MySQL coerced the string to an integer for the id lookup, so the value
 * coincidentally matched an existing alias id. The code then skipped
 * creating a new Alias and tried to sync the raw string as the pivot
 * alias_id, producing:
 *
 *   SQLSTATE[01000]: Warning: 1265 Data truncated for column 'alias_id'
 *
 * The fix adds the is_numeric() guard so non-numeric names always create a
 * new alias row and are synced by their integer id.
 */
class EntityUpdateAliasSyncTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($this->user);
    }

    /** @test */
    public function updating_with_a_non_numeric_alias_name_creates_a_new_alias_without_truncation(): void
    {
        // An existing alias whose id the coerced string will collide with.
        $existing = Alias::create(['name' => 'The Original Alias']);

        $entity = Entity::factory()->create([
            'created_by' => $this->user->id,
            // The factory's faker->name slug fails EntityRequest's ^[a-z0-9-]+$ rule.
            'slug' => 'alias-sync-test-entity',
        ]);

        // A free-text alias name beginning with the existing alias id, e.g. "7D".
        // is_numeric() is false, but MySQL coerces "7D" -> 7 in an id lookup.
        $aliasName = $existing->id.'D';
        $aliasCountBefore = Alias::count();

        $response = $this->put(route('entities.update', $entity), [
            'name' => $entity->name,
            'slug' => $entity->slug,
            'short' => $entity->short ?? 'short',
            'description' => $entity->description ?? 'description',
            'entity_type_id' => $entity->entity_type_id,
            'entity_status_id' => $entity->entity_status_id,
            'alias_list' => [$aliasName],
        ]);

        // Pre-fix this path threw a QueryException (HTTP 500) instead of redirecting.
        $response->assertRedirect(route('entities.show', $entity));

        // A brand-new alias row was created for the free-text name...
        $this->assertSame($aliasCountBefore + 1, Alias::count());

        // ...and it is attached to the entity by its integer id.
        $entity->refresh();
        $newAlias = Alias::where('name', ucwords(strtolower($aliasName)))->firstOrFail();
        $this->assertTrue(
            $entity->aliases->contains('id', $newAlias->id),
            'The newly created alias should be attached to the entity.'
        );

        // The unrelated existing alias must NOT have been attached by coercion.
        $this->assertFalse(
            $entity->aliases->contains('id', $existing->id),
            'The coincidentally-coerced existing alias should not be attached.'
        );
    }
}
