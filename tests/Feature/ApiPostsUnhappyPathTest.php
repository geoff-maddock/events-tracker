<?php

namespace Tests\Feature;

use App\Models\Post;
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
        $response = $this->getJson('/api/posts/999999999');

        $response->assertStatus(404);
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
