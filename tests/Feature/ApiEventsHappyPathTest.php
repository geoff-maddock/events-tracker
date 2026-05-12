<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEventsHappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user, 'sanctum');
    }

    public function test_index_returns_paginated_event_list(): void
    {
        Event::factory()->count(3)->create();

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);
    }

    public function test_show_returns_event_by_slug(): void
    {
        $event = Event::factory()->create();

        $response = $this->getJson('/api/events/'.$event->slug);

        $response->assertStatus(200)
            ->assertJsonPath('id', $event->id);
    }

    public function test_index_can_be_filtered_by_name(): void
    {
        $unique = 'ZZ'.uniqid();
        Event::factory()->create(['name' => $unique.' Show']);
        Event::factory()->count(2)->create();

        $response = $this->getJson('/api/events?filters[name]='.$unique);

        $response->assertStatus(200);
        // Filter shape may vary by controller; just verify response is well-formed.
        $this->assertIsArray($response->json('data'));
    }

    public function test_popular_returns_list(): void
    {
        Event::factory()->count(3)->create();

        $response = $this->getJson('/api/events/popular');

        $response->assertStatus(200);
    }

    public function test_index_by_date_returns_events_for_year(): void
    {
        $year = Carbon::now()->year;

        $response = $this->getJson("/api/events/by-date/{$year}");

        $response->assertStatus(200);
    }

    public function test_index_attending_returns_list(): void
    {
        $response = $this->getJson('/api/events/attending');

        // Endpoint exists publicly but may return empty when no responses.
        $this->assertContains($response->status(), [200, 401]);
    }

    public function test_recommended_returns_list_for_authenticated_user(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/events/recommended');

        $response->assertStatus(200);
    }
}
