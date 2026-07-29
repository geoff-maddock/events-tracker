<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\User;
use App\Models\UserStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EntityQuickStoreTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function activeUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email_verified_at' => Carbon::now(),
            'user_status_id' => UserStatus::ACTIVE,
        ]);

        return $user;
    }

    /** @test */
    public function a_guest_cannot_quick_create_an_entity()
    {
        $this->postJson('/entities/quick-store', ['name' => 'The Smiling Moose', 'role' => 'Venue'])
            ->assertStatus(401);
    }

    /** @test */
    public function an_authenticated_user_can_quick_create_a_venue()
    {
        $user = $this->activeUser();

        $response = $this->actingAs($user)
            ->postJson('/entities/quick-store', ['name' => 'The Smiling Moose', 'role' => 'Venue']);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'name', 'slug'])
            ->assertJson(['name' => 'The Smiling Moose']);

        $entity = Entity::where('name', 'The Smiling Moose')->first();

        $this->assertNotNull($entity);
        $this->assertSame(EntityType::SPACE, $entity->entity_type_id);
        $this->assertSame(EntityStatus::ACTIVE, $entity->entity_status_id);
        $this->assertSame($user->id, $entity->created_by);
        $this->assertTrue($entity->roles->contains('name', 'Venue'));
    }

    /** @test */
    public function an_authenticated_user_can_quick_create_a_promoter()
    {
        $user = $this->activeUser();

        $response = $this->actingAs($user)
            ->postJson('/entities/quick-store', ['name' => 'Drusky Entertainment', 'role' => 'Promoter']);

        $response->assertStatus(201);

        $entity = Entity::where('name', 'Drusky Entertainment')->first();

        $this->assertNotNull($entity);
        $this->assertSame(EntityType::GROUP, $entity->entity_type_id);
        $this->assertTrue($entity->roles->contains('name', 'Promoter'));
    }

    /** @test */
    public function quick_create_generates_a_unique_slug()
    {
        $user = $this->activeUser();

        $first = $this->actingAs($user)
            ->postJson('/entities/quick-store', ['name' => 'Duplicate Hall', 'role' => 'Venue'])
            ->json('slug');

        $second = $this->actingAs($user)
            ->postJson('/entities/quick-store', ['name' => 'Duplicate Hall', 'role' => 'Venue'])
            ->json('slug');

        $this->assertNotSame($first, $second);
    }

    /** @test */
    public function quick_create_requires_a_name_of_at_least_three_characters()
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->postJson('/entities/quick-store', ['name' => 'ab', 'role' => 'Venue'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    /** @test */
    public function quick_create_stores_optional_short_and_full_description()
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->postJson('/entities/quick-store', [
                'name' => 'Mr Smalls Theatre',
                'role' => 'Venue',
                'short' => 'Converted church concert venue',
                'description' => 'A concert venue in Millvale housed in a converted 19th-century church.',
            ])
            ->assertStatus(201);

        $entity = Entity::where('name', 'Mr Smalls Theatre')->first();

        $this->assertNotNull($entity);
        $this->assertSame('Converted church concert venue', $entity->short);
        $this->assertSame('A concert venue in Millvale housed in a converted 19th-century church.', $entity->description);
    }

    /** @test */
    public function quick_create_uses_the_name_as_placeholder_when_descriptions_are_omitted()
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->postJson('/entities/quick-store', ['name' => 'Placeholder Hall', 'role' => 'Venue'])
            ->assertStatus(201);

        $entity = Entity::where('name', 'Placeholder Hall')->first();

        $this->assertSame('Placeholder Hall', $entity->short);
        $this->assertSame('Placeholder Hall', $entity->description);
    }

    /** @test */
    public function quick_create_attaches_an_optional_image_as_primary_photo()
    {
        Storage::fake('external');
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post('/entities/quick-store', [
                'name' => 'Spirit Lodge',
                'role' => 'Venue',
                'image' => UploadedFile::fake()->image('lodge.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(201);

        $entity = Entity::where('name', 'Spirit Lodge')->first();
        $photo = $entity->photos()->first();

        $this->assertNotNull($photo);
        $this->assertSame(1, (int) $photo->is_primary);
        $this->assertSame($user->id, $photo->created_by);
    }

    /** @test */
    public function quick_create_rejects_a_non_image_file()
    {
        Storage::fake('external');
        $user = $this->activeUser();

        $this->actingAs($user)
            ->post('/entities/quick-store', [
                'name' => 'Bad Upload Hall',
                'role' => 'Venue',
                'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');

        $this->assertNull(Entity::where('name', 'Bad Upload Hall')->first());
    }
}
