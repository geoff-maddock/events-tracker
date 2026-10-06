<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A profile lists the entities the user created, shown like the entities they
 * follow, with a "View all entities" link, instead of a full "Pages you manage"
 * list with stats links (#2156).
 */
class UserProfileEntitiesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function user(bool $publicProfile = true): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        Profile::factory()->create(['user_id' => $user->id, 'setting_public_profile' => $publicProfile ? 1 : 0]);

        return $user;
    }

    private function viewAllUrl(User $user): string
    {
        return e(route('entities.index', ['filters' => ['created_by' => $user->id]]));
    }

    public function test_the_profile_lists_created_entities_with_a_view_all_link(): void
    {
        $user = $this->user();
        $entity = Entity::factory()->create(['name' => 'Zz Created Collective', 'created_by' => $user->id, 'entity_status_id' => EntityStatus::ACTIVE]);

        $this->actingAs($user)->get("/users/{$user->id}")->assertOk()
            ->assertSee('Zz Created Collective')
            ->assertSee($this->viewAllUrl($user), false)
            ->assertDontSee('Pages you manage')
            ->assertDontSee(route('entities.stats', $entity), false);
    }

    public function test_other_viewers_see_only_active_entities(): void
    {
        $user = $this->user();
        Entity::factory()->create(['name' => 'Zz Active Collective', 'created_by' => $user->id, 'entity_status_id' => EntityStatus::ACTIVE]);
        Entity::factory()->create(['name' => 'Zz Unlisted Collective', 'created_by' => $user->id, 'entity_status_id' => EntityStatus::UNLISTED]);

        $this->actingAs($this->user())->get("/users/{$user->id}")->assertOk()
            ->assertSee('Zz Active Collective')
            ->assertDontSee('Zz Unlisted Collective');

        // the user sees all of their own
        $this->actingAs($user)->get("/users/{$user->id}")->assertOk()
            ->assertSee('Zz Unlisted Collective');
    }

    public function test_no_entities_means_no_entities_card(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get("/users/{$user->id}")->assertOk()
            ->assertDontSee($this->viewAllUrl($user), false);
    }

    public function test_view_all_lists_only_that_users_entities(): void
    {
        $user = $this->user();
        Entity::factory()->create(['name' => 'Zz Mine Collective', 'created_by' => $user->id, 'entity_status_id' => EntityStatus::ACTIVE]);
        Entity::factory()->create(['name' => 'Zz Someone Else Collective', 'created_by' => $this->user()->id, 'entity_status_id' => EntityStatus::ACTIVE]);

        $this->get(route('entities.index', ['filters' => ['created_by' => $user->id]]))->assertOk()
            ->assertSee('Zz Mine Collective')
            ->assertDontSee('Zz Someone Else Collective');
    }
}
