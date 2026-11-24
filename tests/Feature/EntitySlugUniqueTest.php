<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Role;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * An entity page is found by its slug, so a slug belongs to one entity (#2413).
 * The form and the API refuse a taken slug, in whatever spelling the writer
 * would store as that slug, before it reaches the unique index.
 */
class EntitySlugUniqueTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Entity::factory()->create(['slug' => 'zz-taken', 'created_by' => $this->user->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $slug): array
    {
        return [
            'name' => 'ZZ Slug Entity',
            'slug' => $slug,
            'short' => 'A short ZZ',
            'description' => 'A long ZZ description',
            'entity_type_id' => EntityType::query()->value('id'),
            'entity_status_id' => EntityStatus::ACTIVE,
            'role_list' => [Role::query()->value('id')],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function takenSpellings(): array
    {
        return [
            'the slug itself' => ['zz-taken'],
            'a leading dash' => ['-zz-taken'],
            'a trailing dash' => ['zz-taken-'],
            'a doubled dash' => ['zz--taken'],
        ];
    }

    #[Test]
    public function the_database_refuses_a_second_entity_with_the_same_slug(): void
    {
        $this->assertTrue(Schema::hasIndex('entities', ['slug'], 'unique'));

        $this->expectException(UniqueConstraintViolationException::class);
        Entity::factory()->create(['slug' => 'zz-taken']);
    }

    #[Test]
    public function an_unlisted_entity_holds_its_slug_too(): void
    {
        Entity::factory()->create(['slug' => 'zz-hidden', 'entity_status_id' => EntityStatus::UNLISTED]);

        $this->actingAs($this->user)->post('/entities', $this->payload('zz-hidden'))
            ->assertSessionHasErrors('slug');
    }

    #[Test]
    #[DataProvider('takenSpellings')]
    public function the_form_refuses_a_taken_slug(string $slug): void
    {
        $this->actingAs($this->user)->post('/entities', $this->payload($slug))
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Entity::where('slug', 'zz-taken')->count());
    }

    #[Test]
    #[DataProvider('takenSpellings')]
    public function the_api_refuses_a_taken_slug_on_create_and_on_patch(string $slug): void
    {
        $other = Entity::factory()->create(['slug' => 'zz-other', 'created_by' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum');

        $this->postJson('/api/entities', $this->payload($slug))
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');

        $this->patchJson('/api/entities/zz-other', ['slug' => $slug])
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');

        $this->assertSame('zz-other', $other->fresh()->slug);
    }

    #[Test]
    public function an_entity_can_be_saved_again_under_its_own_slug(): void
    {
        $this->actingAs($this->user)->put('/entities/zz-taken', $this->payload('-zz-taken-'))
            ->assertSessionHasNoErrors();

        $this->assertSame('ZZ Slug Entity', Entity::where('slug', 'zz-taken')->sole()->name);
    }

    #[Test]
    public function a_slug_of_dashes_alone_is_refused(): void
    {
        $this->actingAs($this->user)->post('/entities', $this->payload('---'))
            ->assertSessionHasErrors('slug');
    }

    #[Test]
    public function a_slug_in_the_wrong_format_is_still_refused_as_it_is(): void
    {
        $this->actingAs($this->user)->post('/entities', $this->payload('Has Spaces'))
            ->assertSessionHasErrors('slug');

        $this->assertSame(0, Entity::where('slug', 'has-spaces')->count());
    }
}
