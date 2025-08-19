<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTagsStoreTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @test */
    public function it_returns_validation_error_when_slug_already_exists(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        Tag::factory()->create(['slug' => 'existing-slug']);

        $response = $this->postJson('/api/tags', [
            'name' => 'Another Tag',
            'slug' => 'existing-slug',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['slug']);
    }

    /** @test */
    public function it_creates_a_tag_with_unique_slug(): void
    {
        $user = User::factory()->create(['user_status_id' => 1]);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/tags', [
            'name' => 'New Tag',
            'slug' => 'unique-slug',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['slug' => 'unique-slug']);

        $this->assertDatabaseHas('tags', ['slug' => 'unique-slug']);
    }
}

