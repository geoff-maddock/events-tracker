<?php

namespace Tests\Feature;

use App\Models\Series;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSeriesHappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user, 'sanctum');
    }

    public function test_index_returns_paginated_series_list(): void
    {
        Series::factory()->count(3)->create();

        $response = $this->getJson('/api/series');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
    }

    public function test_show_returns_series_by_slug(): void
    {
        $series = Series::factory()->create();

        $response = $this->getJson('/api/series/'.$series->slug);

        $response->assertStatus(200)
            ->assertJsonPath('id', $series->id);
    }

    public function test_photos_endpoint_returns_a_list(): void
    {
        $series = Series::factory()->create();

        $response = $this->getJson('/api/series/'.$series->slug.'/photos');

        $response->assertStatus(200);
    }

    public function test_popular_returns_a_list(): void
    {
        Series::factory()->count(3)->create();

        $response = $this->getJson('/api/series/popular');

        $response->assertStatus(200);
    }
}
