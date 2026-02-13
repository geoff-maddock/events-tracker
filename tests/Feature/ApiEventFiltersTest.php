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

    /**
     * Test filtering events by a single tag.
     *
     * @return void
     */
    public function testFilterBySingleTag()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        // Create tags
        $jungleTag = Tag::factory()->create([
            'name' => 'Jungle',
            'slug' => 'jungle',
        ]);

        $technoTag = Tag::factory()->create([
            'name' => 'Techno',
            'slug' => 'techno',
        ]);

        $houseTag = Tag::factory()->create([
            'name' => 'House',
            'slug' => 'house',
        ]);

        // Create events
        $jungleEvent = Event::factory()->create([
            'name' => 'Jungle Night',
            'slug' => 'jungle-night',
            'start_at' => Carbon::now()->addDays(1),
        ]);

        $technoEvent = Event::factory()->create([
            'name' => 'Techno Party',
            'slug' => 'techno-party',
            'start_at' => Carbon::now()->addDays(2),
        ]);

        $houseEvent = Event::factory()->create([
            'name' => 'House Music',
            'slug' => 'house-music',
            'start_at' => Carbon::now()->addDays(3),
        ]);

        // Attach tags to events
        $jungleEvent->tags()->attach($jungleTag->id);
        $technoEvent->tags()->attach($technoTag->id);
        $houseEvent->tags()->attach($houseTag->id);

        // Test filtering by single tag
        $response = $this->getJson('/api/events?filters[tag]=jungle');

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
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        // Create tags
        $jungleTag = Tag::factory()->create([
            'name' => 'Jungle',
            'slug' => 'jungle',
        ]);

        $technoTag = Tag::factory()->create([
            'name' => 'Techno',
            'slug' => 'techno',
        ]);

        $houseTag = Tag::factory()->create([
            'name' => 'House',
            'slug' => 'house',
        ]);

        // Create events
        $jungleEvent = Event::factory()->create([
            'name' => 'Jungle Night',
            'slug' => 'jungle-night',
            'start_at' => Carbon::now()->addDays(1),
        ]);

        $technoEvent = Event::factory()->create([
            'name' => 'Techno Party',
            'slug' => 'techno-party',
            'start_at' => Carbon::now()->addDays(2),
        ]);

        $houseEvent = Event::factory()->create([
            'name' => 'House Music',
            'slug' => 'house-music',
            'start_at' => Carbon::now()->addDays(3),
        ]);

        // Attach tags to events
        $jungleEvent->tags()->attach($jungleTag->id);
        $technoEvent->tags()->attach($technoTag->id);
        $houseEvent->tags()->attach($houseTag->id);

        // Test filtering by multiple tags using comma-separated values (OR logic)
        $response = $this->getJson('/api/events?filters[tag]=jungle,techno');

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
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        // Create tags
        $jungleTag = Tag::factory()->create([
            'name' => 'Jungle',
            'slug' => 'jungle',
        ]);

        $technoTag = Tag::factory()->create([
            'name' => 'Techno',
            'slug' => 'techno',
        ]);

        $houseTag = Tag::factory()->create([
            'name' => 'House',
            'slug' => 'house',
        ]);

        // Create events
        $jungleEvent = Event::factory()->create([
            'name' => 'Jungle Night',
            'slug' => 'jungle-night',
            'start_at' => Carbon::now()->addDays(1),
        ]);

        $technoEvent = Event::factory()->create([
            'name' => 'Techno Party',
            'slug' => 'techno-party',
            'start_at' => Carbon::now()->addDays(2),
        ]);

        $houseEvent = Event::factory()->create([
            'name' => 'House Music',
            'slug' => 'house-music',
            'start_at' => Carbon::now()->addDays(3),
        ]);

        // Attach tags to events
        $jungleEvent->tags()->attach($jungleTag->id);
        $technoEvent->tags()->attach($technoTag->id);
        $houseEvent->tags()->attach($houseTag->id);

        // Test filtering by multiple tags using array format (OR logic)
        $response = $this->getJson('/api/events?filters[tag][]=jungle&filters[tag][]=techno');

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
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        // Create tags
        $jungleTag = Tag::factory()->create([
            'name' => 'Jungle',
            'slug' => 'jungle',
        ]);

        $technoTag = Tag::factory()->create([
            'name' => 'Techno',
            'slug' => 'techno',
        ]);

        $houseTag = Tag::factory()->create([
            'name' => 'House',
            'slug' => 'house',
        ]);

        // Create event with multiple tags
        $multiTagEvent = Event::factory()->create([
            'name' => 'Multi Genre Night',
            'slug' => 'multi-genre-night',
            'start_at' => Carbon::now()->addDays(1),
        ]);

        $houseEvent = Event::factory()->create([
            'name' => 'House Music',
            'slug' => 'house-music',
            'start_at' => Carbon::now()->addDays(3),
        ]);

        // Attach multiple tags to the same event
        $multiTagEvent->tags()->attach([$jungleTag->id, $technoTag->id]);
        $houseEvent->tags()->attach($houseTag->id);

        // Test filtering by one of the tags
        $response = $this->getJson('/api/events?filters[tag]=jungle');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Multi Genre Night'])
            ->assertJsonMissing(['name' => 'House Music']);

        // Test filtering by multiple tags - should still return the event once
        $response = $this->getJson('/api/events?filters[tag]=jungle,techno');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Multi Genre Night'])
            ->assertJsonMissing(['name' => 'House Music']);
    }
}
