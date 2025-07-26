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

        $tag = Tag::factory()->create();
        $entity = Entity::factory()->create();
        $series = Series::factory()->create();

        $eventTag = Event::factory()->create(['start_at' => Carbon::now()->addDay()]);
        $eventTag->tags()->attach($tag);

        $eventEntity = Event::factory()->create(['start_at' => Carbon::now()->addDays(2)]);
        $eventEntity->entities()->attach($entity);

        $eventSeries = Event::factory()->create([
            'start_at' => Carbon::now()->addDays(3),
            'series_id' => $series->id,
        ]);

        $otherEvent = Event::factory()->create(['start_at' => Carbon::now()->addDays(4)]);

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

