<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiActivitiesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function testIndexEndpoint()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        Activity::factory()->count(3)->create();

        $response = $this->getJson('/api/activities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
                'first_page_url',
                'from',
                'last_page',
                'last_page_url',
                'next_page_url',
                'path',
                'per_page',
                'prev_page_url',
                'to',
                'total',
            ]);
    }

    public function testShowEndpoint()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        $activity = Activity::factory()->create([
            'object_table' => 'Event',
            'object_name' => 'Test Event',
            'message' => 'Created event Test Event',
        ]);

        $response = $this->getJson('/api/activities/' . $activity->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $activity->id,
                'object_table' => 'Event',
                'object_name' => 'Test Event',
                'message' => 'Created event Test Event',
            ]);
    }

    public function testFilterByObjectTable()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        Activity::factory()->create(['object_table' => 'Event']);
        Activity::factory()->create(['object_table' => 'Entity']);

        $response = $this->getJson('/api/activities?filters[object_table]=Event');

        $response->assertStatus(200)
            ->assertJsonFragment(['object_table' => 'Event'])
            ->assertJsonMissing(['object_table' => 'Entity']);
    }

    public function testFilterByUserId()
    {
        $user1 = User::factory()->create(['user_status_id' => 1]);
        $user2 = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user1);

        Activity::factory()->create(['user_id' => $user1->id]);
        Activity::factory()->create(['user_id' => $user2->id]);

        $response = $this->getJson('/api/activities?filters[user_id]=' . $user1->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['user_id' => $user1->id])
            ->assertJsonMissing(['user_id' => $user2->id]);
    }

    public function testSorting()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        $activity1 = Activity::factory()->create(['object_name' => 'Alpha']);
        $activity2 = Activity::factory()->create(['object_name' => 'Zulu']);

        $response = $this->getJson('/api/activities?sort=activities.object_name&direction=asc');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals('Alpha', $data[0]['object_name']);
    }

    public function testPagination()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        Activity::factory()->count(15)->create();

        $response = $this->getJson('/api/activities?limit=5');

        $response->assertStatus(200)
            ->assertJsonPath('per_page', 5)
            ->assertJsonCount(5, 'data');
    }
}
