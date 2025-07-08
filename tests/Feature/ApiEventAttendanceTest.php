<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventResponse;
use App\Models\ResponseType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEventAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // public function test_user_can_attend_event()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     // ResponseType::factory()->create(['id' => 1, 'name' => 'Attending']);

    //     $event = Event::factory()->create();

    //     $response = $this->postJson("/api/events/{$event->id}/attend");

    //     $response->assertStatus(200);

    //     $this->assertDatabaseHas('event_responses', [
    //         'event_id' => $event->id,
    //         'user_id' => $user->id,
    //         'response_type_id' => 1,
    //     ]);
    // }

    // public function test_user_can_unattend_event()
    // {
    //     $user = User::factory()->create();
    //     $this->actingAs($user, 'sanctum');

    //     // ResponseType::factory()->create(['id' => 1, 'name' => 'Attending']);

    //     $event = Event::factory()->create();

    //     EventResponse::create([
    //         'event_id' => $event->id,
    //         'user_id' => $user->id,
    //         'response_type_id' => 1,
    //     ]);

    //     $response = $this->deleteJson("/api/events/{$event->id}/attend");

    //     $response->assertStatus(204);

    //     $this->assertDatabaseMissing('event_responses', [
    //         'event_id' => $event->id,
    //         'user_id' => $user->id,
    //         'response_type_id' => 1,
    //     ]);
    // }

    public function test_attend_request_does_not_create_duplicates()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        // ResponseType::factory()->create(['id' => 1, 'name' => 'Attending']);

        $event = Event::factory()->create();

        EventResponse::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'response_type_id' => 1,
        ]);

        $response = $this->postJson("/api/events/{$event->id}/attend");

        $response->assertStatus(200);

        $this->assertEquals(1, EventResponse::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('response_type_id', 1)
            ->count());
    }
}
