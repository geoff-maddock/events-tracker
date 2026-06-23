<?php

namespace Tests\Feature;

use App\Http\Requests\ListQueryParameters;
use App\Models\Event;
use App\Models\User;
use App\Models\UserStatus;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ensures list endpoints cannot be coerced into an unbounded page size.
 * A crafted ?limit is clamped to ListQueryParameters::MAX_LIMIT both on
 * the shared listing path (getLimit) and on the bespoke popular endpoint.
 */
class ApiListLimitCapTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user, 'sanctum');
    }

    /** @test */
    public function events_index_caps_an_oversized_limit(): void
    {
        $response = $this->getJson('/api/events?limit=999999');

        $response->assertStatus(200);
        $this->assertSame(ListQueryParameters::MAX_LIMIT, $response->json('per_page'));
    }

    /** @test */
    public function events_index_honors_a_reasonable_limit(): void
    {
        $response = $this->getJson('/api/events?limit=50');

        $response->assertStatus(200);
        $this->assertSame(50, $response->json('per_page'));
    }

    /** @test */
    public function popular_caps_an_oversized_limit_and_does_not_error(): void
    {
        Event::factory()->create(['visibility_id' => Visibility::VISIBILITY_PUBLIC]);

        $response = $this->getJson('/api/events/popular?limit=999999&days=99999');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(ListQueryParameters::MAX_LIMIT, (int) $response->json('per_page'));
    }
}
