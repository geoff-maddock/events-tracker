<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class ApiRecommendedEventsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_recommended_events_endpoint_returns_followed_events()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        // creates a tag, an entity, and a series
        $tag = Tag::factory()->create();
        $entity = Entity::factory()->create();
        $series = Series::factory()->create();

        // creates an event that starts one day in the future and associates it with the tag
        $eventTag = Event::factory()->create([
            'start_at' => Carbon::now()->addDay(),
            'visibility_id' => 3,   // Assuming 3 = Public visibility  
        ]);
        $eventTag->tags()->attach($tag);

        // creates an event that starts two days in the future and associates it with the entity
        $eventEntity = Event::factory()->create([
            'start_at' => Carbon::now()->addDays(2),
            'visibility_id' => 3,   // Assuming 3 = Public visibility
        ]);
        $eventEntity->entities()->attach($entity);

        // creates an event that starts three days in the future and associates it with the series
        $eventSeries = Event::factory()->create([
            'start_at' => Carbon::now()->addDays(3),
            'series_id' => $series->id,
            'visibility_id' => 3,   // Assuming 3 = Public visibility
        ]);

        // creates another event that starts four days in the future, which should not be returned because it has no association with the user
        // or the followed entities, tags, or series
        $otherEvent = Event::factory()->create(['start_at' => Carbon::now()->addDays(4)]);

        // have the user follow the tag, entity, and series
        Follow::create(['object_id' => $tag->id, 'user_id' => $user->id, 'object_type' => 'tag']);
        Follow::create(['object_id' => $entity->id, 'user_id' => $user->id, 'object_type' => 'entity']);
        Follow::create(['object_id' => $series->id, 'user_id' => $user->id, 'object_type' => 'series']);

        $response = $this->getJson('/api/events/recommended');

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $eventTag->id])
            ->assertJsonFragment(['id' => $eventEntity->id])
            ->assertJsonFragment(['id' => $eventSeries->id])
            ->assertJsonMissing(['id' => $otherEvent->id]);
    }
}

