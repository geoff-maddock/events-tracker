<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Entity;
use App\Models\Event;
use App\Models\Forum;
use App\Models\Link;
use App\Models\Location;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API destroy endpoints that only required authentication, so any signed-in
 * user could delete another user's record (#2139).
 *
 * The primary signal is that the row survives a denied request.
 */
class ApiDestroyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    private function makeUser(?string $group = null): User
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        if ($group) {
            $user->assignGroup($group);
        }

        return $user;
    }

    // Users

    public function test_member_cannot_delete_another_user(): void
    {
        $target = $this->makeUser();

        $this->actingAs($this->makeUser(), 'sanctum')
            ->deleteJson('/api/users/'.$target->id)
            ->assertForbidden();

        $this->assertNotNull(User::find($target->id));
    }

    public function test_user_can_delete_their_own_account(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/users/'.$user->id)
            ->assertNoContent();

        $this->assertNull(User::find($user->id));
    }

    public function test_admin_can_delete_another_user(): void
    {
        $target = $this->makeUser();

        $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->deleteJson('/api/users/'.$target->id)
            ->assertNoContent();

        $this->assertNull(User::find($target->id));
    }

    // Forums

    public function test_member_cannot_delete_a_forum(): void
    {
        $member = $this->makeUser();
        $forum = Forum::factory()->create(['created_by' => $member->id]);

        $this->actingAs($member, 'sanctum')
            ->deleteJson('/api/forums/'.$forum->id)
            ->assertForbidden();

        $this->assertNotNull(Forum::find($forum->id));
    }

    // Activities

    public function test_member_cannot_delete_an_activity(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs($this->makeUser(), 'sanctum')
            ->deleteJson('/api/activities/'.$activity->id)
            ->assertForbidden();

        $this->assertNotNull(Activity::find($activity->id));
    }

    public function test_admin_can_delete_an_activity(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->deleteJson('/api/activities/'.$activity->id)
            ->assertNoContent();

        $this->assertNull(Activity::find($activity->id));
    }

    // Locations

    public function test_non_owner_cannot_delete_a_location(): void
    {
        $entity = Entity::factory()->create(['created_by' => $this->makeUser()->id]);
        $location = Location::factory()->create([
            'entity_id' => $entity->id,
            'created_by' => $entity->created_by,
        ]);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->deleteJson('/api/locations/'.$location->id)
            ->assertForbidden();

        $this->assertNotNull(Location::find($location->id));
    }

    public function test_entity_owner_can_delete_its_location(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);
        $location = Location::factory()->create([
            'entity_id' => $entity->id,
            'created_by' => $this->makeUser()->id,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson('/api/locations/'.$location->id)
            ->assertNoContent();

        $this->assertNull(Location::find($location->id));
    }

    // Links

    public function test_non_owner_cannot_delete_a_link(): void
    {
        $entity = Entity::factory()->create(['created_by' => $this->makeUser()->id]);
        $link = Link::factory()->create();
        $entity->links()->attach($link->id);

        $this->actingAs($this->makeUser(), 'sanctum')
            ->deleteJson('/api/links/'.$link->id)
            ->assertForbidden();

        $this->assertNotNull(Link::find($link->id));
    }

    public function test_entity_owner_can_delete_its_link(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);
        $link = Link::factory()->create();
        $entity->links()->attach($link->id);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson('/api/links/'.$link->id)
            ->assertNoContent();

        $this->assertNull(Link::find($link->id));
    }

    // Events, series, entities: owners were already allowed; admins now are too

    public function test_admin_can_delete_an_event_they_do_not_own(): void
    {
        $event = Event::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->deleteJson('/api/events/'.$event->slug)
            ->assertNoContent();

        $this->assertNull(Event::find($event->id));
    }

    public function test_admin_can_delete_a_series_they_do_not_own(): void
    {
        $series = Series::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->deleteJson('/api/series/'.$series->slug)
            ->assertNoContent();

        $this->assertNull(Series::find($series->id));
    }

    public function test_admin_can_delete_an_entity_they_do_not_own(): void
    {
        $entity = Entity::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->actingAs($this->makeUser('admin'), 'sanctum')
            ->deleteJson('/api/entities/'.$entity->slug)
            ->assertNoContent();

        $this->assertNull(Entity::find($entity->id));
    }
}
