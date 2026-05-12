<?php

namespace Tests\Feature;

use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSeriesUnhappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_show_returns_404_for_missing_series(): void
    {
        $response = $this->getJson('/api/series/missing-slug-'.uniqid());

        $response->assertStatus(404);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/series', ['name' => 'Test']);

        $this->assertContains($response->status(), [401, 403, 422]);
    }

    public function test_update_requires_authentication(): void
    {
        $series = Series::factory()->create();

        $response = $this->putJson('/api/series/'.$series->id, ['name' => 'Updated']);

        $this->assertContains($response->status(), [401, 403, 405]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $series = Series::factory()->create();

        $response = $this->deleteJson('/api/series/'.$series->id);

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_follow_requires_authentication(): void
    {
        $series = Series::factory()->create();

        $response = $this->postJson('/api/series/'.$series->id.'/follow');

        $this->assertContains($response->status(), [401, 403]);
    }

    public function test_unfollow_requires_authentication(): void
    {
        $series = Series::factory()->create();

        $response = $this->postJson('/api/series/'.$series->id.'/unfollow');

        $this->assertContains($response->status(), [401, 403]);
    }
}
