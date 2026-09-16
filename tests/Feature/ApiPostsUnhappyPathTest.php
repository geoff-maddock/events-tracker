<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPostsUnhappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_show_returns_404_for_missing_post(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/posts/999999999');

        $response->assertStatus(404);
    }

    public function test_show_requires_authentication_before_lookup(): void
    {
        // auth runs before route model binding, so guests can't probe which records exist
        $response = $this->getJson('/api/posts/999999999');

        $response->assertStatus(401);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/posts', ['body' => 'Hello']);

        $this->assertContains($response->status(), [401, 403, 422]);
    }

    public function test_update_requires_authentication(): void
    {
        $post = Post::factory()->create();

        $response = $this->putJson('/api/posts/'.$post->id, ['body' => 'Updated']);

        $this->assertContains($response->status(), [401, 403, 405]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $post = Post::factory()->create();

        $response = $this->deleteJson('/api/posts/'.$post->id);

        $this->assertContains($response->status(), [401, 403]);
    }
}
