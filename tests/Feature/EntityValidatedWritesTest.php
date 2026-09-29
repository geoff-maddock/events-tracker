<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Entity writes save validated input only, and the rules cover every field
 * the entity form and the API send (#2180).
 */
class EntityValidatedWritesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $owner;

    private Entity $entity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();

        $this->owner = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        // creating with created_by also makes that user an owner (Entity's created hook)
        $this->entity = Entity::factory()->create(['slug' => 'zz-validated-entity', 'created_by' => $this->owner->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload(string $slug): array
    {
        return [
            'name' => 'ZZ Validated Entity',
            'slug' => $slug,
            'short' => 'A short ZZ',
            'description' => 'A long ZZ description',
            'entity_type_id' => EntityType::query()->value('id'),
            'entity_status_id' => EntityStatus::query()->value('id'),
            'started_at' => '2015-06-01',
            'facebook_username' => 'zzfacebook',
            'instagram_username' => 'zzinstagram',
            'twitter_username' => 'zztwitter',
            'role_list' => [Role::query()->value('id')],
        ];
    }

    private function assertSaved(Entity $entity): void
    {
        $this->assertSame('zzfacebook', $entity->facebook_username);
        $this->assertSame('zzinstagram', $entity->instagram_username);
        $this->assertSame('zztwitter', $entity->twitter_username);
        $this->assertSame('2015-06-01', $entity->started_at->format('Y-m-d'));
        $this->assertSame([Role::query()->value('id')], $entity->roles()->pluck('roles.id')->all());
    }

    public function test_creating_and_updating_an_entity_saves_every_form_field(): void
    {
        $this->actingAs($this->owner)->post('/entities', $this->formPayload('zz-created-entity'))
            ->assertSessionHasNoErrors();
        $created = Entity::where('slug', 'zz-created-entity')->sole();
        $this->assertSaved($created);
        $this->assertSame($this->owner->id, (int) $created->created_by);

        $this->actingAs($this->owner)->put("/entities/{$this->entity->slug}", $this->formPayload('zz-validated-entity'))
            ->assertSessionHasNoErrors();
        $this->assertSaved($this->entity->fresh());
    }

    public function test_entity_fields_are_validated_and_created_by_is_never_taken_from_the_body(): void
    {
        $other = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);

        $this->actingAs($this->owner)
            ->put("/entities/{$this->entity->slug}", ['role_list' => [999999], 'twitter_username' => str_repeat('z', 65)] + $this->formPayload('zz-validated-entity'))
            ->assertSessionHasErrors(['role_list.0', 'twitter_username']);

        $this->actingAs($this->owner, 'sanctum')
            ->patchJson("/api/entities/{$this->entity->slug}", ['instagram_username' => 'zzpatched', 'created_by' => $other->id])
            ->assertOk();
        $this->assertSame('zzpatched', $this->entity->fresh()->instagram_username);
        $this->assertSame($this->owner->id, (int) $this->entity->fresh()->created_by);
    }

    public function test_api_entity_location_writes_are_validated_and_saved(): void
    {
        $this->actingAs($this->owner, 'sanctum');
        $payload = [
            'name' => 'ZZ Room', 'slug' => 'zz-room', 'city' => 'Pittsburgh',
            'visibility_id' => Visibility::VISIBILITY_PUBLIC, 'location_type_id' => Location::factory()->make()->location_type_id,
            'address_one' => '1 ZZ Street', 'latitude' => 40.44, 'capacity' => 120,
        ];

        $this->postJson("/api/entities/{$this->entity->id}/locations", ['latitude' => 'north'] + $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('latitude');

        $this->postJson("/api/entities/{$this->entity->id}/locations", $payload)->assertStatus(201);
        $location = Location::where('slug', 'zz-room')->sole();
        $this->assertSame('1 ZZ Street', $location->address_one);
        $this->assertSame(120, (int) $location->capacity);
    }

    public function test_patching_an_entity_location_cannot_move_it_to_another_entity(): void
    {
        $location = Location::factory()->create(['entity_id' => $this->entity->id, 'slug' => 'zz-kept-room']);
        $someoneElses = Entity::factory()->create();

        $this->actingAs($this->owner, 'sanctum')
            ->patchJson("/api/entities/{$this->entity->id}/locations/{$location->id}", [
                'entity_id' => $someoneElses->id,
                'neighborhood' => 'ZZ Hood',
            ])
            ->assertOk();

        $this->assertSame($this->entity->id, (int) $location->fresh()->entity_id);
        $this->assertSame('ZZ Hood', $location->fresh()->neighborhood);
    }

    public function test_entity_controllers_do_not_pass_raw_request_input_to_model_writes(): void
    {
        foreach (['EntitiesController', 'Api/EntitiesController'] as $controller) {
            $source = file_get_contents(app_path("Http/Controllers/{$controller}.php"));
            $this->assertDoesNotMatchRegularExpression(
                '/(create|fill|update)\(\$request->(all|input)\(\)\)|\$input = \$request->(all|input)\(\);/',
                $source,
                "{$controller} passes raw request input to a model write"
            );
        }
    }
}
