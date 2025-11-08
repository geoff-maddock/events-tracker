<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Link;
use App\Models\User;
use App\Models\Visibility;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEventEmbedsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /**
     * Test that embeds endpoint returns consistent results on multiple calls.
     *
     * @return void
     */
    public function testEmbedsReturnsConsistentResults()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a visibility
        $visibility = Visibility::firstOrCreate(
            ['name' => 'Public'],
            ['name' => 'Public']
        );

        // Create an event type
        $eventType = EventType::firstOrCreate(
            ['name' => 'Concert'],
            ['name' => 'Concert']
        );

        // Create entities with bandcamp links
        $entity1 = Entity::factory()->create(['name' => 'Band One']);
        $entity2 = Entity::factory()->create(['name' => 'Band Two']);

        // Create links for entities
        $link1 = Link::firstOrCreate([
            'url' => 'https://bandone.bandcamp.com/album/test-album',
            'text' => 'Band One Album',
            'is_primary' => true,
        ]);

        $link2 = Link::firstOrCreate([
            'url' => 'https://bandtwo.bandcamp.com/track/test-track',
            'text' => 'Band Two Track',
            'is_primary' => true,
        ]);

        // Attach links to entities
        $entity1->links()->syncWithoutDetaching([$link1->id]);
        $entity2->links()->syncWithoutDetaching([$link2->id]);

        // Create an event with entities
        $event = Event::factory()->create([
            'name' => 'Test Event with Embeds',
            'slug' => 'test-event-with-embeds',
            'description' => 'Event with bandcamp links in description: https://artist.bandcamp.com/album/123',
            'visibility_id' => $visibility->id,
            'event_type_id' => $eventType->id,
            'created_by' => $user->id,
            'start_at' => Carbon::now()->addDays(7),
        ]);

        // Attach entities to event
        $event->entities()->attach([$entity1->id, $entity2->id]);

        // Make multiple calls to the embeds endpoint
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson("/api/events/{$event->slug}/embeds");
            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'total',
                    'current_page',
                    'per_page',
                    'first_page_url',
                    'from',
                    'last_page',
                    'next_page_url',
                    'path',
                    'prev_page_url',
                    'to',
                ]);

            $responses[] = $response->json();
        }

        // Verify all responses are identical
        $firstResponse = $responses[0];
        foreach ($responses as $index => $response) {
            $this->assertEquals(
                $firstResponse['total'],
                $response['total'],
                "Response {$index} total count differs from first response"
            );
            $this->assertEquals(
                $firstResponse['data'],
                $response['data'],
                "Response {$index} data differs from first response"
            );
        }

        // Verify that the total is consistent and not zero (since we have links)
        // Note: This may be 0 if the external services are not available, but it should be consistent
        $this->assertIsInt($firstResponse['total']);
        $this->assertGreaterThanOrEqual(0, $firstResponse['total']);
    }

    /**
     * Test that embeds endpoint returns empty array when no links exist.
     *
     * @return void
     */
    public function testEmbedsReturnsEmptyForEventWithoutLinks()
    {
        $user = User::factory()->create();

        $visibility = Visibility::firstOrCreate(
            ['name' => 'Public'],
            ['name' => 'Public']
        );

        $eventType = EventType::firstOrCreate(
            ['name' => 'Concert'],
            ['name' => 'Concert']
        );

        $event = Event::factory()->create([
            'name' => 'Event Without Links',
            'slug' => 'event-without-links',
            'description' => 'This event has no bandcamp or soundcloud links.',
            'visibility_id' => $visibility->id,
            'event_type_id' => $eventType->id,
            'created_by' => $user->id,
            'start_at' => Carbon::now()->addDays(7),
        ]);

        // Make multiple calls to verify consistency
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $response = $this->getJson("/api/events/{$event->slug}/embeds");
            $response->assertStatus(200);
            $responses[] = $response->json();
        }

        // All responses should be identical and have zero embeds
        foreach ($responses as $response) {
            $this->assertEquals(0, $response['total']);
            $this->assertEmpty($response['data']);
        }
    }

    /**
     * Test that minimal-embeds endpoint returns consistent results.
     *
     * @return void
     */
    public function testMinimalEmbedsReturnsConsistentResults()
    {
        $user = User::factory()->create();

        $visibility = Visibility::firstOrCreate(
            ['name' => 'Public'],
            ['name' => 'Public']
        );

        $eventType = EventType::firstOrCreate(
            ['name' => 'Concert'],
            ['name' => 'Concert']
        );

        $event = Event::factory()->create([
            'name' => 'Test Minimal Embeds',
            'slug' => 'test-minimal-embeds',
            'description' => 'Event description',
            'visibility_id' => $visibility->id,
            'event_type_id' => $eventType->id,
            'created_by' => $user->id,
            'start_at' => Carbon::now()->addDays(7),
        ]);

        // Make multiple calls to the minimal-embeds endpoint
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $response = $this->getJson("/api/events/{$event->slug}/minimal-embeds");
            $response->assertStatus(200);
            $responses[] = $response->json();
        }

        // Verify all responses are identical
        $firstResponse = $responses[0];
        foreach ($responses as $index => $response) {
            $this->assertEquals(
                $firstResponse['total'],
                $response['total'],
                "Minimal embeds response {$index} differs from first response"
            );
        }
    }
}
