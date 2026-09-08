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
    public function quick_check_reports_which_alias_matched()
    {
        $entity = $this->makeEntity('Spirit Lodge');
        $alias = Alias::create(['name' => 'The Lodge PGH']);
        $entity->aliases()->attach($alias->id);

        $response = $this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=the lodge pgh')
            ->assertStatus(200);

        $match = collect($response->json('data'))->firstWhere('id', $entity->id);
        $this->assertNotNull($match);
        $this->assertSame('The Lodge PGH', $match['alias']);

        $byName = $this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=Spirit Lodge')
            ->json('data');
        $this->assertNull(collect($byName)->firstWhere('id', $entity->id)['alias']);
    }

    /** @test */
    public function quick_check_can_be_restricted_to_one_entity_type()
    {
        $space = $this->makeEntity('Spirit Lodge', EntityType::SPACE);
        $group = $this->makeEntity('Spirit Lodge', EntityType::GROUP);

        $ids = collect($this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=Spirit Lodge&entity_type_id='.EntityType::GROUP)
            ->assertStatus(200)
            ->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($group->id));
        $this->assertFalse($ids->contains($space->id));
    }

    /** @test */
    public function quick_check_type_filter_still_matches_aliases()
    {
        $entity = $this->makeEntity('Spirit Lodge', EntityType::SPACE);
        $alias = Alias::create(['name' => 'The Lodge PGH']);
        $entity->aliases()->attach($alias->id);

        $user = $this->activeUser();

        $this->actingAs($user)
            ->getJson('/entities/quick-check?name=The Lodge PGH&entity_type_id='.EntityType::SPACE)
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $entity->id]);

        $this->actingAs($user)
            ->getJson('/entities/quick-check?name=The Lodge PGH&entity_type_id='.EntityType::GROUP)
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $entity->id]);
    }

    /** @test */
    public function quick_check_rejects_an_unknown_entity_type()
    {
        $this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=Spirit Lodge&entity_type_id=9999')
            ->assertStatus(422)
            ->assertJsonValidationErrors('entity_type_id');
    }

    /** @test */
    public function quick_check_can_exclude_the_entity_being_edited()
    {
        $self = $this->makeEntity('Spirit Lodge');
        $other = $this->makeEntity('Spirit Lodge Annex');

        $ids = collect($this->actingAs($this->activeUser())
            ->getJson('/entities/quick-check?name=Spirit Lodge&exclude_id='.$self->id)
            ->assertStatus(200)
            ->json('data'))->pluck('id');

        $this->assertFalse($ids->contains($self->id));
        $this->assertTrue($ids->contains($other->id));
    }

    /** @test */
    public function entity_create_and_edit_forms_render_the_duplicate_warning()
    {
        $user = $this->activeUser();
        $entity = $this->makeEntity('Spirit Lodge');
        $entity->update(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get('/entities/create')
            ->assertStatus(200)
            ->assertSee('id="entity-duplicate-warning"', false)
            ->assertSee('const excludeId = 0;', false);

        $this->actingAs($user)
            ->get('/entities/'.$entity->slug.'/edit')
            ->assertStatus(200)
            ->assertSee('id="entity-duplicate-warning"', false)
            ->assertSee('const excludeId = '.$entity->id.';', false);
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
