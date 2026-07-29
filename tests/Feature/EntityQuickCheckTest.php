<?php

namespace Tests\Feature;

use App\Models\Alias;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\User;
use App\Models\UserStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityQuickCheckTest extends TestCase
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

    private function makeEntity(string $name, int $typeId = EntityType::SPACE): Entity
    {
        return Entity::factory()->create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'entity_type_id' => $typeId,
            'entity_status_id' => EntityStatus::ACTIVE,
        ]);
    }

    /** @test */
    public function a_guest_cannot_use_quick_check()
    {
        $this->getJson('/entities/quick-check?name=Spirit')
            ->assertStatus(401);
    }

    /** @test */
    public function quick_check_requires_a_name_of_at_least_three_characters()
    {
        $this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=ab')
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    /** @test */
    public function quick_check_finds_an_exact_name_match()
    {
        $entity = $this->makeEntity('Spirit Lodge');

        $this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=Spirit Lodge')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $entity->id, 'name' => 'Spirit Lodge']);
    }

    /** @test */
    public function quick_check_finds_a_substring_match()
    {
        $entity = $this->makeEntity('The Spirit Lodge Hall');

        $this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=spirit lodge')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $entity->id]);
    }

    /** @test */
    public function quick_check_finds_a_close_misspelling()
    {
        $entity = $this->makeEntity('Spirit Lodge');

        $this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=Sprit Lodge')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $entity->id]);
    }

    /** @test */
    public function quick_check_matches_entities_of_any_type_and_returns_the_type_label()
    {
        $this->makeEntity('Spirit Collective', EntityType::GROUP);

        $response = $this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=Spirit Collective')
            ->assertStatus(200);

        $match = collect($response->json('data'))->firstWhere('name', 'Spirit Collective');
        $this->assertNotNull($match);
        $this->assertSame('Group', $match['entity_type']);
    }

    /** @test */
    public function quick_check_matches_an_entity_alias()
    {
        $entity = $this->makeEntity('Spirit Lodge');
        $alias = Alias::create(['name' => 'The Lodge PGH']);
        $entity->aliases()->attach($alias->id);

        $this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=The Lodge PGH')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $entity->id]);
    }

    /** @test */
    public function quick_check_returns_nothing_for_an_unrelated_name()
    {
        $this->makeEntity('Spirit Lodge');

        $this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=Completely Different Venue Xyz')
            ->assertStatus(200)
            ->assertJsonMissing(['name' => 'Spirit Lodge']);
    }

    /** @test */
    public function quick_check_returns_at_most_five_matches()
    {
        foreach (range(1, 8) as $i) {
            $this->makeEntity('Spirit Venue '.$i);
        }

        $response = $this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=Spirit Venue')
            ->assertStatus(200);

        $this->assertLessThanOrEqual(5, count($response->json('data')));
    }
}
