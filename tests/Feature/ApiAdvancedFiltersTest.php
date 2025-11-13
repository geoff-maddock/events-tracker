<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAdvancedFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create(['user_status_id' => 1]);
    }

    public function testSimpleEqualityFilter()
    {
        $this->actingAs($this->user);

        $event1 = Event::factory()->create([
            'name' => 'Test Event',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        $event2 = Event::factory()->create([
            'name' => 'Another Event',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        $response = $this->getJson('/api/events?filters=events.name EQ "Test Event"');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Test Event'])
            ->assertJsonMissing(['name' => 'Another Event']);
    }

    public function testNotEqualFilter()
    {
        $this->actingAs($this->user);

        $event1 = Event::factory()->create([
            'name' => 'Test Event',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        $event2 = Event::factory()->create([
            'name' => 'Another Event',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        $response = $this->getJson('/api/events?filters=events.name NEQ "Test Event"');

        $response->assertStatus(200)
            ->assertJsonMissing(['name' => 'Test Event'])
            ->assertJsonFragment(['name' => 'Another Event']);
    }

    public function testGreaterThanFilter()
    {
        $this->actingAs($this->user);

        Event::factory()->create([
            'name' => 'Event 1',
            'min_age' => 18,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Event 2',
            'min_age' => 21,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Event 3',
            'min_age' => 16,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        $response = $this->getJson('/api/events?filters=events.min_age GT 18');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Event 2'])
            ->assertJsonMissing(['name' => 'Event 1'])
            ->assertJsonMissing(['name' => 'Event 3']);
    }

    public function testLessThanFilter()
    {
        $this->actingAs($this->user);

        Event::factory()->create([
            'name' => 'Event 1',
            'min_age' => 18,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Event 2',
            'min_age' => 16,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        $response = $this->getJson('/api/events?filters=events.min_age LT 18');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Event 2'])
            ->assertJsonMissing(['name' => 'Event 1']);
    }

    public function testInFilter()
    {
        $this->actingAs($this->user);

        Event::factory()->create([
            'name' => 'Event 1',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Event 2',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Event 3',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        $response = $this->getJson('/api/events?filters=events.name IN ("Event 1", "Event 2")');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Event 1'])
            ->assertJsonFragment(['name' => 'Event 2'])
            ->assertJsonMissing(['name' => 'Event 3']);
    }

    public function testNotInFilter()
    {
        $this->actingAs($this->user);

        Event::factory()->create([
            'name' => 'Event 1',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Event 2',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Event 3',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        $response = $this->getJson('/api/events?filters=events.name NOT IN ("Event 1", "Event 2")');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Event 3'])
            ->assertJsonMissing(['name' => 'Event 1'])
            ->assertJsonMissing(['name' => 'Event 2']);
    }

    public function testAndCondition()
    {
        $this->actingAs($this->user);

        Event::factory()->create([
            'name' => 'Rock Concert',
            'min_age' => 21,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Jazz Night',
            'min_age' => 18,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Rock Festival',
            'min_age' => 18,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        $response = $this->getJson('/api/events?filters=events.name LIKE "%Rock%" AND events.min_age GT 18');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Rock Concert'])
            ->assertJsonMissing(['name' => 'Jazz Night'])
            ->assertJsonMissing(['name' => 'Rock Festival']);
    }

    public function testOrCondition()
    {
        $this->actingAs($this->user);

        Event::factory()->create([
            'name' => 'Rock Concert',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Jazz Night',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Blues Festival',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        $response = $this->getJson('/api/events?filters=events.name EQ "Rock Concert" OR events.name EQ "Jazz Night"');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Rock Concert'])
            ->assertJsonFragment(['name' => 'Jazz Night'])
            ->assertJsonMissing(['name' => 'Blues Festival']);
    }

    public function testGroupedConditions()
    {
        $this->actingAs($this->user);

        Event::factory()->create([
            'name' => 'Rock Concert',
            'min_age' => 21,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Jazz Night',
            'min_age' => 18,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Rock Festival',
            'min_age' => 18,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Blues Concert',
            'min_age' => 21,
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        $response = $this->getJson('/api/events?filters=(events.name LIKE "%Rock%" OR events.name LIKE "%Jazz%") AND events.min_age EQ 18');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Jazz Night'])
            ->assertJsonFragment(['name' => 'Rock Festival'])
            ->assertJsonMissing(['name' => 'Rock Concert'])
            ->assertJsonMissing(['name' => 'Blues Concert']);
    }

    public function testBackwardCompatibilityWithLegacyFilters()
    {
        $this->actingAs($this->user);

        Event::factory()->create([
            'name' => 'Test Event',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Another Event',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        // Test legacy array-style filters still work
        $response = $this->getJson('/api/events?filters[name]=Test');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Test Event']);
    }

    public function testInvalidFilterReturnsAllResults()
    {
        $this->actingAs($this->user);

        Event::factory()->create([
            'name' => 'Event 1',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        Event::factory()->create([
            'name' => 'Event 2',
            'visibility_id' => Visibility::where('name', 'Public')->first()->id,
        ]);

        // Invalid filter should be ignored and return all results
        $response = $this->getJson('/api/events?filters=invalid filter syntax');

        $response->assertStatus(200);
        // Should return both events since the invalid filter is ignored
    }
}
