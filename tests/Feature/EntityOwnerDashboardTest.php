<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\EntityStatDaily;
use App\Models\Event;
use App\Models\EventReachDaily;
use App\Models\EventShare;
use App\Models\Follow;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserStatus;
use App\Services\EntityStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Owner analytics page for an entity (#2149).
 */
class EntityOwnerDashboardTest extends TestCase
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

    private function follow(Entity $entity, Carbon $at): void
    {
        $follow = new Follow();
        $follow->forceFill([
            'user_id' => $this->makeUser()->id,
            'object_type' => 'entity',
            'object_id' => $entity->id,
            'created_at' => $at,
            'updated_at' => $at,
        ])->save();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $entity = Entity::factory()->create();

        $this->get(route('entities.stats', $entity))->assertRedirect('/login');
    }

    public function test_non_owner_is_forbidden(): void
    {
        $entity = Entity::factory()->create(['created_by' => $this->makeUser()->id]);

        $this->actingAs($this->makeUser())->get(route('entities.stats', $entity))->assertForbidden();
    }

    public function test_previous_owner_loses_access_after_a_transfer(): void
    {
        $creator = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $creator->id]);
        $entity->syncOwners([$this->makeUser()->id]);

        $this->actingAs($creator)->get(route('entities.stats', $entity))->assertForbidden();
    }

    public function test_owner_and_admin_can_view_an_empty_dashboard(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)->get(route('entities.stats', $entity))
            ->assertOk()
            ->assertSee('Page views')
            ->assertSee('Nothing to chart yet');

        $this->actingAs($this->makeUser('admin'))
            ->get(route('entities.stats', ['entity' => $entity, 'period' => 90]))
            ->assertOk()
            ->assertSee('Last 90 days');
    }

    public function test_dashboard_numbers_come_from_tracking_follows_and_reach(): void
    {
        $owner = $this->makeUser();
        $entity = Entity::factory()->create(['created_by' => $owner->id]);

        // 12 views and 3 clicks in the last 30 days, 4 views in the 30 days before
        EntityStatDaily::create(['entity_id' => $entity->id, 'date' => Carbon::today()->toDateString(), 'views' => 5, 'clicks' => 1]);
        EntityStatDaily::create(['entity_id' => $entity->id, 'date' => Carbon::today()->subDays(10)->toDateString(), 'views' => 7, 'clicks' => 2]);
        EntityStatDaily::create(['entity_id' => $entity->id, 'date' => Carbon::today()->subDays(40)->toDateString(), 'views' => 4]);

        $this->follow($entity, Carbon::now()->subDays(2));
        $this->follow($entity, Carbon::now()->subDays(50));

        // the entity is billed on one upcoming event that reached 9 digest inboxes and was posted to Instagram
        $event = Event::factory()->create(['start_at' => Carbon::now()->addDays(5)]);
        $event->entities()->attach($entity->id);
        EventReachDaily::create(['event_id' => $event->id, 'date' => Carbon::today()->subDays(3)->toDateString(), 'channel' => EventReachDaily::CHANNEL_DIGEST, 'count' => 9]);
        $share = new EventShare();
        $share->forceFill(['event_id' => $event->id, 'platform' => 'instagram', 'platform_id' => 'abc', 'created_at' => Carbon::now()->subDay()])->save();

        $stats = app(EntityStats::class)->dashboard($entity, [30, 90]);

        $this->assertSame(['current' => 12, 'previous' => 4], $stats['periods'][30]['views']);
        $this->assertSame(['current' => 3, 'previous' => 0], $stats['periods'][30]['clicks']);
        $this->assertSame(['current' => 1, 'previous' => 1], $stats['periods'][30]['follows']);
        $this->assertSame(2, $stats['followers']);
        $this->assertSame(1, $stats['upcomingEvents']);
        $this->assertSame(['digest' => 9, 'instagram' => 1, 'discord' => 0], $stats['reach'][30]);
        $this->assertCount(90, $stats['chart']['labels']);
        $this->assertSame(5, end($stats['chart']['views']));

        $this->actingAs($owner)->get(route('entities.stats', $entity))
            ->assertOk()
            ->assertSee('entityStatsChart', false)
            ->assertSee('+200%');
    }

    public function test_owner_sees_stats_links_on_their_profile_and_entity_page(): void
    {
        $owner = $this->makeUser();
        Profile::factory()->create(['user_id' => $owner->id]);
        $entity = Entity::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)->get('/users/'.$owner->id)
            ->assertOk()
            ->assertSee('Pages you manage')
            ->assertSee(route('entities.stats', $entity), false);

        $this->actingAs($owner)->get(route('entities.show', $entity))
            ->assertSee('View Stats');

        $this->actingAs($this->makeUser())->get(route('entities.show', $entity))
            ->assertDontSee('View Stats');
    }
}
