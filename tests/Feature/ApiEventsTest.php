<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Tag;
use App\Models\User;
use App\Models\Visibility;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

}
