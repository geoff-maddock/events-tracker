<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiVisibilitiesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function testIndexEndpoint()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        Visibility::factory()->count(3)->create();

        $response = $this->getJson('/api/visibilities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data',
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

    public function testShowEndpoint()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        $visibility = Visibility::factory()->create(['name' => 'Public']);

        $response = $this->getJson('/api/visibilities/' . $visibility->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $visibility->id,
                'name' => 'Public',
            ]);
    }

    public function testFilterEndpoint()
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user);

        Visibility::factory()->create(['name' => 'Private']);
        $target = Visibility::factory()->create(['name' => 'Public']);

        $response = $this->getJson('/api/visibilities/filter?name=Public');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Public'])
            ->assertJsonMissing(['name' => 'Private']);
    }
}
