<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\DiscordTargetCriterion;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Event;
use App\Models\EventStatus;
use App\Models\EventType;
use App\Models\Group;
use App\Models\Role;
use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Types, statuses and roles that are still in use can't be deleted (#2238).
 * The deletes used to swallow the foreign-key error and answer 204/success,
 * cascade into the series using an event type, or strip a role from every
 * entity; entity types and statuses were deleted out from under entities.
 */
class LookupDeleteInUseTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->admin = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->admin->groups()->attach(Group::firstOrCreate(['name' => 'admin'])->id);
    }

    private function eventType(): EventType
    {
        return EventType::factory()->create(['name' => 'ZZ Type']);
    }

    private function eventStatus(): EventStatus
    {
        return EventStatus::create(['name' => 'ZZ Status']);
    }

    private function entityType(): EntityType
    {
        return EntityType::create(['name' => 'ZZ Kind', 'slug' => 'zz-kind-'.uniqid(), 'short' => 'ZZ']);
    }

    private function entityStatus(): EntityStatus
    {
        return EntityStatus::create(['name' => 'ZZ State']);
    }

    private function role(): Role
    {
        return Role::create(['name' => 'Zz role', 'slug' => 'zz-role-'.uniqid(), 'short' => 'ZZ']);
    }

    /**
     * @return array<string, array{0: string, 1: callable(): Model, 2: callable(Model): void}>
     */
    public static function apiLookups(): array
    {
        return [
            'event type used by an event' => ['event-types', fn ($t) => $t->eventType(), fn ($m) => Event::factory()->create(['event_type_id' => $m->id])],
            'event type used only by a series' => ['event-types', fn ($t) => $t->eventType(), fn ($m) => Series::factory()->create(['event_type_id' => $m->id])],
            'event status' => ['event-statuses', fn ($t) => $t->eventStatus(), fn ($m) => Event::factory()->create(['event_status_id' => $m->id])],
            'entity type' => ['entity-types', fn ($t) => $t->entityType(), fn ($m) => Entity::factory()->create(['entity_type_id' => $m->id])],
            'entity status' => ['entity-statuses', fn ($t) => $t->entityStatus(), fn ($m) => Entity::factory()->create(['entity_status_id' => $m->id])],
            'role' => ['roles', fn ($t) => $t->role(), fn ($m) => Entity::factory()->create()->roles()->attach($m->id)],
            'event type used only by a Discord target' => ['event-types', fn ($t) => $t->eventType(), fn ($m) => DiscordTargetCriterion::factory()->create(['criteria_type' => DiscordTargetCriterion::TYPE_EVENT_TYPE, 'criteria_id' => $m->id])],
        ];
    }

    #[DataProvider('apiLookups')]
    public function test_an_api_delete_of_a_record_in_use_is_refused(string $uri, callable $make, callable $use): void
    {
        $this->actingAs($this->admin);
        $record = $make($this);
        $use($record);
        $usersBefore = $this->usageCount($record);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/{$uri}/{$record->getRouteKey()}")
            ->assertStatus(409)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'still in use'));

        $this->assertNotNull($record->fresh());
        $this->assertSame($usersBefore, $this->usageCount($record));
        // nothing was deleted, so no delete is logged
        $this->assertSame(0, $this->deleteLogCount($record));
    }

    #[DataProvider('apiLookups')]
    public function test_an_api_delete_of_an_unused_record_succeeds(string $uri, callable $make): void
    {
        $record = $make($this);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/{$uri}/{$record->getRouteKey()}")
            ->assertNoContent();

        $this->assertNull($record->fresh());
        $this->assertSame(1, $this->deleteLogCount($record));
    }

    public function test_the_web_entity_type_delete_refuses_one_in_use(): void
    {
        $type = $this->entityType();
        Entity::factory()->create(['entity_type_id' => $type->id]);

        $this->actingAs($this->admin)->delete("/entity-types/{$type->getRouteKey()}")
            ->assertRedirect('/entity-types')
            ->assertSessionHas('flash_message', fn ($f) => $f['level'] === 'error');
        $this->assertNotNull($type->fresh());

        $unused = $this->entityType();
        $this->actingAs($this->admin)->delete("/entity-types/{$unused->getRouteKey()}")->assertRedirect('/entity-types');
        $this->assertNull($unused->fresh());
    }

    public function test_the_web_role_delete_refuses_one_in_use(): void
    {
        $role = $this->role();
        $entity = Entity::factory()->create();
        $entity->roles()->attach($role->id);

        $this->actingAs($this->admin)->delete("/roles/{$role->getRouteKey()}")
            ->assertRedirect('/roles')
            ->assertSessionHas('flash_message', fn ($f) => $f['level'] === 'error');
        $this->assertNotNull($role->fresh());
        $this->assertTrue($entity->roles()->whereKey($role->id)->exists());

        $unused = $this->role();
        $this->actingAs($this->admin)->delete("/roles/{$unused->getRouteKey()}")->assertRedirect('/roles');
        $this->assertNull($unused->fresh());
    }

    public function test_an_event_type_can_be_created_through_the_api(): void
    {
        // slug is required and NOT NULL but wasn't fillable, so every create was a 500
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/event-types', ['name' => 'Zz New Type', 'slug' => 'zz-new-type'])
            ->assertOk();

        $this->assertSame(1, EventType::where('name', 'Zz New Type')->count());
    }

    private function deleteLogCount(Model $record): int
    {
        return Activity::where('object_table', class_basename($record))
            ->where('object_id', $record->getKey())
            ->where('action_id', 3)
            ->count();
    }

    private function usageCount(Model $record): int
    {
        return match (true) {
            $record instanceof EventType => Event::where('event_type_id', $record->id)->count() + Series::where('event_type_id', $record->id)->count()
                + DiscordTargetCriterion::where('criteria_type', DiscordTargetCriterion::TYPE_EVENT_TYPE)->where('criteria_id', $record->id)->count(),
            $record instanceof EventStatus => Event::where('event_status_id', $record->id)->count(),
            $record instanceof EntityType => Entity::where('entity_type_id', $record->id)->count(),
            $record instanceof EntityStatus => Entity::where('entity_status_id', $record->id)->count(),
            $record instanceof Role => $record->entities()->count(),
            default => 0,
        };
    }
}
