<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEventsUnhappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_show_returns_404_for_missing_event(): void
    {
        $response = $this->getJson('/api/events/999999');

        $response->assertStatus(404);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/events', [
            'name' => 'Test Event',
        ]);

        $this->assertContains($response->status(), [401, 403, 419]);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/events', []);

        $response->assertStatus(422);
    }

    public function test_update_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->putJson('/api/events/'.$event->id, [
            'name' => 'Updated',
        ]);

        $this->assertContains($response->status(), [401, 403, 419]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->deleteJson('/api/events/'.$event->id);

        $this->assertContains($response->status(), [401, 403, 419]);
    }

    public function test_attend_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->postJson('/api/events/'.$event->id.'/attend');

        $this->assertContains($response->status(), [401, 403, 419]);
    }

    public function test_recommended_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/events/recommended');

        $this->assertContains($response->status(), [401, 403]);
    }
}
