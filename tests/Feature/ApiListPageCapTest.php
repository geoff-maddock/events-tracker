<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserStatus;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ensures list endpoints cannot be coerced into an unbounded pagination page.
 * A crafted ?page (e.g. page=792390418) otherwise makes Laravel build a
 * range() of that many page URLs when rendering pagination links, allocating
 * gigabytes and exhausting the request memory limit (EVENTREPO-WB). The global
 * currentPageResolver clamps the page to AppServiceProvider::MAX_PAGE.
 */
class ApiListPageCapTest extends TestCase
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
    public function events_index_caps_an_oversized_page_and_does_not_error(): void
    {
        $response = $this->getJson('/api/events?page=792390418');

        $response->assertStatus(200);
        $this->assertSame(AppServiceProvider::MAX_PAGE, $response->json('current_page'));
    }

    /** @test */
    public function events_index_honors_a_reasonable_page(): void
    {
        $response = $this->getJson('/api/events?page=2');

        $response->assertStatus(200);
        $this->assertSame(2, $response->json('current_page'));
    }
}
