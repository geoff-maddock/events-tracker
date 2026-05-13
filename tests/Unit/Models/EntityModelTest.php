<?php

namespace Tests\Unit\Models;

use App\Models\Alias;
use App\Models\Entity;
use App\Models\EntityStatus;
use App\Models\EntityType;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityModelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_of_type_scope_filters_by_entity_type_name(): void
    {
        $type = EntityType::first();
        $this->assertNotNull($type, 'Entity types must be seeded.');

        $entity = Entity::factory()->create(['entity_type_id' => $type->id]);

        $results = Entity::ofType($type->name)->get();

        $this->assertTrue($results->contains('id', $entity->id));
    }

    public function test_owned_by_scope_filters_to_users_entities(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $owned = Entity::factory()->create();
        $owned->forceFill(['created_by' => $owner->id])->save();

        $notOwned = Entity::factory()->create();
        $notOwned->forceFill(['created_by' => $other->id])->save();

        $results = Entity::ownedBy($owner)->get();

        $this->assertTrue($results->contains('id', $owned->id));
        $this->assertFalse($results->contains('id', $notOwned->id));
    }

    public function test_active_scope_returns_only_active_entities(): void
    {
        $active = EntityStatus::where('name', 'Active')->first();
        $this->assertNotNull($active, 'Active entity status must be seeded.');

        $entity = Entity::factory()->create(['entity_status_id' => $active->id]);

        $results = Entity::active()->get();

        $this->assertTrue($results->contains('id', $entity->id));
        foreach ($results as $row) {
            $this->assertEquals($active->id, $row->entity_status_id);
        }
    }

    public function test_all_ordered_returns_entities_sorted_by_name(): void
    {
        Entity::factory()->create(['name' => 'ZZ-'.uniqid()]);
        Entity::factory()->create(['name' => 'AA-'.uniqid()]);

        $results = Entity::allOrdered();

        $sorted = $results->pluck('name')->toArray();
        $reference = $sorted;
        sort($reference, SORT_STRING);
        $this->assertEquals($reference, $sorted);
    }

    public function test_get_alias_string_returns_empty_string_with_no_aliases(): void
    {
        $entity = Entity::factory()->create();

        $this->assertSame('', $entity->getAliasString());
    }

    public function test_get_role_string_returns_empty_string_with_no_roles(): void
    {
        $entity = Entity::factory()->create();

        $this->assertSame('', $entity->getRoleString());
    }

    public function test_get_role_string_returns_comma_separated_role_names(): void
    {
        $entity = Entity::factory()->create();
        $role = Role::first();
        $this->assertNotNull($role);
        $entity->roles()->attach($role->id);

        $this->assertStringContainsString($role->name, $entity->fresh()->getRoleString());
    }

    public function test_get_alias_string_returns_comma_separated_alias_names(): void
    {
        $entity = Entity::factory()->create();
        $alias = Alias::create(['name' => 'ZZ-Alias-One']);
        $entity->aliases()->attach($alias->id);

        $this->assertSame('ZZ-Alias-One', $entity->fresh()->getAliasString());
    }

    public function test_todays_events_returns_events_starting_today(): void
    {
        $entity = Entity::factory()->create();
        $todayEvent = Event::factory()->create(['start_at' => Carbon::now()->setTime(20, 0)]);
        $todayEvent->entities()->attach($entity->id);
        Event::factory()->create(['start_at' => Carbon::now()->addDays(5)]); // future, different entity

        $today = $entity->fresh()->todaysEvents();

        $this->assertGreaterThanOrEqual(1, $today->count());
        $this->assertTrue($today->pluck('id')->contains($todayEvent->id));
    }

    public function test_future_events_returns_paginator_of_future_events(): void
    {
        $entity = Entity::factory()->create();
        $future = Event::factory()->create(['start_at' => Carbon::now()->addDays(7)]);
        $future->entities()->attach($entity->id);

        $paginator = $entity->fresh()->futureEvents();

        $this->assertGreaterThanOrEqual(1, $paginator->total());
    }
}
