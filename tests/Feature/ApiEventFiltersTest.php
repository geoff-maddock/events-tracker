<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use App\Models\EventType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEventFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected $jungleTag;
    protected $technoTag;
    protected $houseTag;
    protected $jungleEvent;
    protected $technoEvent;
    protected $houseEvent;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate user
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        // Create tags with unique names to avoid conflicts with seeded data
        $this->jungleTag = Tag::factory()->create([
            'name' => 'Test Jungle Filter',
            'slug' => 'test-jungle-filter',
        ]);

        $this->technoTag = Tag::factory()->create([
            'name' => 'Test Techno Filter',
            'slug' => 'test-techno-filter',
        ]);

        $this->houseTag = Tag::factory()->create([
            'name' => 'Test House Filter',
            'slug' => 'test-house-filter',
        ]);

        // Create events
        $this->jungleEvent = Event::factory()->create([
            'name' => 'Jungle Night',
            'slug' => 'jungle-night',
            'start_at' => Carbon::now()->addDays(1),
        ]);

        $this->technoEvent = Event::factory()->create([
            'name' => 'Techno Party',
            'slug' => 'techno-party',
            'start_at' => Carbon::now()->addDays(2),
        ]);

        $this->houseEvent = Event::factory()->create([
            'name' => 'House Music',
            'slug' => 'house-music',
            'start_at' => Carbon::now()->addDays(3),
        ]);

        // Attach tags to events
        $this->jungleEvent->tags()->attach($this->jungleTag->id);
        $this->technoEvent->tags()->attach($this->technoTag->id);
        $this->houseEvent->tags()->attach($this->houseTag->id);
    }

    /**
     * Test filtering events by a single tag.
     *
     * @return void
     */
    public function testFilterBySingleTag()
    {
        // Test filtering by single tag
        $response = $this->getJson('/api/events?filters[tag]=test-jungle-filter');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Jungle Night'])
            ->assertJsonMissing(['name' => 'Techno Party'])
            ->assertJsonMissing(['name' => 'House Music']);
    }

    /**
     * Test filtering events by multiple tags (OR logic).
     *
     * @return void
     */
    public function testFilterByMultipleTags()
    {
        // Test filtering by multiple tags using comma-separated values (OR logic)
        $response = $this->getJson('/api/events?filters[tag]=test-jungle-filter,test-techno-filter');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Jungle Night'])
            ->assertJsonFragment(['name' => 'Techno Party'])
            ->assertJsonMissing(['name' => 'House Music']);
    }

    /**
     * Test filtering events by multiple tags using array format.
     *
     * @return void
     */
    public function testFilterByMultipleTagsArray()
    {
        // Test filtering by multiple tags using array format (OR logic)
        $response = $this->getJson('/api/events?filters[tag][]=test-jungle-filter&filters[tag][]=test-techno-filter');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Jungle Night'])
            ->assertJsonFragment(['name' => 'Techno Party'])
            ->assertJsonMissing(['name' => 'House Music']);
    }

    /**
     * Test filtering events with multiple tags on the same event.
     *
     * @return void
     */
    public function testFilterEventWithMultipleTags()
    {
        // Create event with multiple tags
        $multiTagEvent = Event::factory()->create([
            'name' => 'Multi Genre Night',
            'slug' => 'multi-genre-night',
            'start_at' => Carbon::now()->addDays(1),
        ]);

        // Attach multiple tags to the same event
        $multiTagEvent->tags()->attach([$this->jungleTag->id, $this->technoTag->id]);

        // Test filtering by one of the tags
        $response = $this->getJson('/api/events?filters[tag]=test-jungle-filter');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Multi Genre Night']);

        // Test filtering by multiple tags - should still return the event once
        $response = $this->getJson('/api/events?filters[tag]=test-jungle-filter,test-techno-filter');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Multi Genre Night'])
            ->assertJsonMissing(['name' => 'House Music']);
    }
}
