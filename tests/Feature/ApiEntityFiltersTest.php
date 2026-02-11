<?php

namespace Tests\Feature;

use App\Models\Alias;
use App\Models\Entity;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEntityFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function testFilterMatchesNameOrAlias()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        $nameMatch = Entity::factory()->create([
            'name' => 'Alpha Co',
            'slug' => 'alpha-co',
        ]);

        $aliasEntity = Entity::factory()->create([
            'name' => 'Other Co',
            'slug' => 'other-co',
        ]);

        $alias = Alias::create(['name' => 'Alpha']);
        $aliasEntity->aliases()->attach($alias);

        $other = Entity::factory()->create([
            'name' => 'Gamma Co',
            'slug' => 'gamma-co',
        ]);

        $response = $this->getJson('/api/entities?filters[name]=Alpha');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Alpha Co'])
            ->assertJsonFragment(['name' => 'Other Co'])
            ->assertJsonMissing(['name' => 'Gamma Co']);
    }

    public function testFilterByActiveRange()
    {
        // Freeze time for consistent test behavior
        Carbon::setTestNow(Carbon::now());

        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        // Create entities
        $recentActiveEntity = Entity::factory()->create([
            'name' => 'Recent Active Entity',
            'slug' => 'recent-active-entity',
        ]);

        $oldActiveEntity = Entity::factory()->create([
            'name' => 'Old Active Entity',
            'slug' => 'old-active-entity',
        ]);

        $neverActiveEntity = Entity::factory()->create([
            'name' => 'Never Active Entity',
            'slug' => 'never-active-entity',
        ]);

        // Create events with different dates
        $recentEvent = Event::factory()->create([
            'name' => 'Recent Event',
            'slug' => 'recent-event',
            'start_at' => Carbon::now()->subMonths(3),
        ]);

        $oldEvent = Event::factory()->create([
            'name' => 'Old Event',
            'slug' => 'old-event',
            'start_at' => Carbon::now()->subYears(3),
        ]);

        // Attach entities to events
        $recentActiveEntity->events()->attach($recentEvent->id);
        $oldActiveEntity->events()->attach($oldEvent->id);

        // Test 1-year filter - should only return recent active entity
        $response = $this->getJson('/api/entities?filters[active_range]=1-year');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Recent Active Entity'])
            ->assertJsonMissing(['name' => 'Old Active Entity'])
            ->assertJsonMissing(['name' => 'Never Active Entity']);

        // Test 5-years filter - should return both recent and old active entities
        $response = $this->getJson('/api/entities?filters[active_range]=5-years');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Recent Active Entity'])
            ->assertJsonFragment(['name' => 'Old Active Entity'])
            ->assertJsonMissing(['name' => 'Never Active Entity']);

        // Clean up - reset test time
        Carbon::setTestNow();
    }
}

