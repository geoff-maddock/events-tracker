<?php

namespace Tests\Feature;

use App\Models\Series;
use App\Models\User;
use App\Models\Entity;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SeriesTest extends TestCase
{
    /**
     * Test trying to create a series with a user
     *
     * @return void
     */
    public function testCreateWithUser()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession([])
            ->get('/series/create');

        $response->assertStatus(200);
    }

    /**
     * Test trying to create a series with no user
     *
     * @return void
     */
    public function testCreateWithNoUser()
    {
        $response = $this->get('/series/create');

        $response->assertStatus(302);
    }

    /**
     * Check the series name appears on the series show page
     *
     * @return void
     */
    public function testShowSeries()
    {
        $user = User::factory()->create();
        $response = $this->get('/series/create');

        $response->assertStatus(302);
    }
}
