<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use App\Models\Entity;
use App\Models\Series;
use App\Models\EventType;
use App\Models\ResponseType;
use App\Models\EventResponse;
use App\Models\Thread;
use App\Models\Follow;
use App\Services\Embeds\EmbedExtractor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class ApiEventsTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /**
     * Test retrieving events list via API endpoint.
     *
     * @return void
     */
    public function testIndexEndpoint()
    {
        $user = User::factory()->create();
        $user->user_status_id = 1; // Assuming 1 is the ID for active status
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/events');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'start_date',
                            'end_date',
                            'location',
                            'visibility_id',
                            'created_at',
                            'updated_at',
                        ]
                    ],
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

    /**
     * Test retrieving events by date
     * 
     * @return void
     */
    public function testIndexByDateEndpoint()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $today = Carbon::now();
        $year = $today->year;
        $month = $today->format('m');
        $day = $today->format('d');

        // Test with year only
        $response = $this->getJson("/api/events/by-date/{$year}");
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'current_page',
                    'total'
                ]);

        // Test with year and month
        $response = $this->getJson("/api/events/by-date/{$year}/{$month}");
        $response->assertStatus(200);

        // Test with year, month and day
        $response = $this->getJson("/api/events/by-date/{$year}/{$month}/{$day}");
        $response->assertStatus(200);
    }


    // /**
    //  * Test showing an event
    //  *
    //  * @return void
    //  */
    // public function testShow()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     // Create a visibility
    //     $visibility = Visibility::factory()->create(['name' => 'Public']);
        
    //     // Create an event
    //     $event = Event::factory()->create([
    //         'visibility_id' => $visibility->id,
    //         'created_by' => $user->id
    //     ]);

    //     $response = $this->getJson("/api/events/{$event->id}");
    //     $response->assertStatus(200)
    //             ->assertJson(fn (AssertableJson $json) => 
    //                 $json->has('id')
    //                     ->has('name')
    //                     ->has('description')
    //                     ->etc()
    //             );
    // }

    // /**
    //  * Test create an event
    //  *
    //  * @return void
    //  */
    // public function testStore()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $visibility = Visibility::factory()->create(['name' => 'Public']);
    //     $eventType = EventType::factory()->create();
        
    //     $eventData = [
    //         'name' => 'Test Event',
    //         'slug' => 'test-event',
    //         'short' => 'Short description',
    //         'description' => 'Full description of the event',
    //         'visibility_id' => $visibility->id,
    //         'event_type_id' => $eventType->id,
    //         'start_at' => Carbon::now()->addDays(5)->toDateTimeString(),
    //         'end_at' => Carbon::now()->addDays(5)->addHours(2)->toDateTimeString(),
    //         'tag_list' => []
    //     ];

    //     $response = $this->postJson('/api/events', $eventData);
        
    //     $response->assertStatus(200)
    //             ->assertJson(fn (AssertableJson $json) => 
    //                 $json->where('title', 'Test Event')
    //                     ->where('slug', 'test-event')
    //                     ->etc()
    //             );
        
    //     $this->assertDatabaseHas('events', [
    //         'name' => 'Test Event',
    //         'slug' => 'test-event'
    //     ]);
    // }

    // /**
    //  * Test updating an event
    //  *
    //  * @return void
    //  */
    // public function testUpdate()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $visibility = Visibility::factory()->create(['name' => 'Public']);
    //     $eventType = EventType::factory()->create();

    //     $event = Event::factory()->create([
    //         'name' => 'Original Title',
    //         'visibility_id' => $visibility->id,
    //         'event_type_id' => $eventType->id,
    //         'created_by' => $user->id
    //     ]);

    //     $updatedData = [
    //         'name' => 'Updated Title',
    //         'slug' => 'updated-title',
    //         'short' => 'Updated short description',
    //         'description' => 'Updated full description',
    //         'visibility_id' => $visibility->id,
    //         'event_type_id' => $eventType->id,
    //         'start_at' => Carbon::now()->addDays(5)->toDateTimeString(),
    //         'end_at' => Carbon::now()->addDays(5)->addHours(2)->toDateTimeString(),
    //         'tag_list' => []
    //     ];

    //     $response = $this->putJson("/api/events/{$event->id}", $updatedData);
        
    //     $response->assertStatus(200);
        
    //     $this->assertDatabaseHas('events', [
    //         'id' => $event->id,
    //         'name' => 'Updated Title',
    //         'slug' => 'updated-title'
    //     ]);
    // }
    
    // /**
    //  * Test deleting an event
    //  *
    //  * @return void
    //  */
    // public function testDestroy()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $event = Event::factory()->create([
    //         'created_by' => $user->id
    //     ]);

    //     $response = $this->deleteJson("/api/events/{$event->id}");
    //     $response->assertStatus(204);
        
    //     $this->assertDatabaseMissing('events', [
    //         'id' => $event->id
    //     ]);
    // }

    // /**
    //  * Test getting embeds for an event
    //  * 
    //  * @return void
    //  */
    // public function testEmbeds()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $event = Event::factory()->create([
    //         'created_by' => $user->id,
    //         'description' => 'Check out this link: https://example.com'
    //     ]);

    //     $response = $this->getJson("/api/events/{$event->id}/embeds");
    //     $response->assertStatus(200)
    //             ->assertJsonStructure([
    //                 'data',
    //                 'total',
    //                 'current_page',
    //                 'per_page'
    //             ]);
    // }

    // /**
    //  * Test getting minimal embeds for an event
    //  * 
    //  * @return void
    //  */
    // public function testMinimalEmbeds()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $event = Event::factory()->create([
    //         'created_by' => $user->id,
    //         'description' => 'Check out this link: https://example.com'
    //     ]);

    //     $response = $this->getJson("/api/events/{$event->id}/minimal-embeds");
    //     $response->assertStatus(200)
    //             ->assertJsonStructure([
    //                 'data',
    //                 'total',
    //                 'current_page',
    //                 'per_page'
    //             ]);
    // }

    // /**
    //  * Test marking a user as attending an event
    //  * 
    //  * @return void
    //  */
    // public function testAttend()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     ResponseType::factory()->create(['id' => 1, 'name' => 'Attending']);

    //     $event = Event::factory()->create();

    //     $response = $this->postJson("/api/events/{$event->id}/attend");
        
    //     // The API endpoint might redirect if it's not properly set up for JSON responses
    //     // so we check both cases
    //     $response->assertStatus(200);
        
    //     $this->assertDatabaseHas('event_responses', [
    //         'event_id' => $event->id,
    //         'user_id' => $user->id,
    //         'response_type_id' => 1
    //     ]);
    // }

    // /**
    //  * Test marking a user as not attending an event
    //  * 
    //  * @return void
    //  */
    // public function testUnattend()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $responseType = ResponseType::factory()->create(['id' => 1, 'name' => 'Attending']);
    //     $event = Event::factory()->create();
        
    //     // First create an attendance record
    //     EventResponse::factory()->create([
    //         'event_id' => $event->id,
    //         'user_id' => $user->id,
    //         'response_type_id' => $responseType->id
    //     ]);

    //     $response = $this->postJson("/api/events/{$event->id}/unattend");
        
    //     // Depending on the implementation
    //     $response->assertStatus(200);
        
    //     $this->assertDatabaseMissing('event_responses', [
    //         'event_id' => $event->id,
    //         'user_id' => $user->id,
    //         'response_type_id' => 1
    //     ]);
    // }

    // /**
    //  * Test following an event
    //  * 
    //  * @return void
    //  */
    // public function testFollow()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $event = Event::factory()->create();

    //     $response = $this->postJson("/api/events/{$event->id}/follow");
        
    //     // Response might vary depending on implementation
    //     $this->assertDatabaseHas('follows', [
    //         'object_id' => $event->id,
    //         'user_id' => $user->id,
    //         'object_type' => 'event'
    //     ]);
    // }

    // /**
    //  * Test unfollowing an event
    //  * 
    //  * @return void
    //  */
    // public function testUnfollow()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $event = Event::factory()->create();
        
    //     // First create a follow record
    //     Follow::factory()->create([
    //         'object_id' => $event->id,
    //         'user_id' => $user->id,
    //         'object_type' => 'event'
    //     ]);

    //     $response = $this->postJson("/api/events/{$event->id}/unfollow");
        
    //     // Response might vary depending on implementation
    //     $this->assertDatabaseMissing('follows', [
    //         'object_id' => $event->id,
    //         'user_id' => $user->id,
    //         'object_type' => 'event'
    //     ]);
    // }
    
    // /**
    //  * Test getting event photos
    //  * 
    //  * @return void
    //  */
    // public function testPhotos()
    // {
    //     Storage::fake('external');

    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $event = Event::factory()->create();

    //     $response = $this->getJson("/api/events/{$event->id}/photos");
    //     $response->assertStatus(200);
    // }

    // /**
    //  * Test the calendar events API
    //  * 
    //  * @return void
    //  */
    // public function testCalendarEventsApi()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $start = Carbon::now()->startOfMonth()->format('Y-m-d');
    //     $end = Carbon::now()->endOfMonth()->format('Y-m-d');

    //     $response = $this->getJson("/api/calendar-events?start={$start}&end={$end}");
    //     $response->assertStatus(200)
    //             ->assertJsonStructure([
    //                 '*' => [
    //                     'id',
    //                     'start',
    //                     'title',
    //                     'url'
    //                 ]
    //             ]);
    // }

    // /**
    //  * Test the tag calendar events API
    //  * 
    //  * @return void
    //  */
    // public function testTagCalendarEventsApi()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $start = Carbon::now()->startOfMonth()->format('Y-m-d');
    //     $end = Carbon::now()->endOfMonth()->format('Y-m-d');

    //     $response = $this->getJson("/api/tag-calendar-events?start={$start}&end={$end}");
    //     $response->assertStatus(200);
    // }

    // /**
    //  * Test creating a thread from an event
    //  * 
    //  * @return void
    //  */
    // public function testCreateThread()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     $event = Event::factory()->create([
    //         'created_by' => $user->id
    //     ]);

    //     $response = $this->postJson("/api/events/{$event->id}/create-thread");
        
    //     // This might redirect, so we test if a thread was created
    //     $this->assertDatabaseHas('threads', [
    //         'event_id' => $event->id
    //     ]);
    // }
}
