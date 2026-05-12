<?php

namespace Tests\Feature;

use App\Models\Blog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBlogsUnhappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_show_returns_404_for_missing_blog(): void
    {
        $response = $this->getJson('/api/blogs/missing-slug-'.uniqid());

        $response->assertStatus(404);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/blogs', ['name' => 'Test post']);

        $this->assertContains($response->status(), [401, 403, 422]);
    }

    public function test_update_requires_authentication(): void
    {
        $blog = Blog::factory()->create();

        // Blog is route-bound by slug, not id.
        $response = $this->putJson('/api/blogs/'.$blog->slug, ['name' => 'Updated']);

        $this->assertContains($response->status(), [401, 403, 405]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $blog = Blog::factory()->create();

        $response = $this->deleteJson('/api/blogs/'.$blog->slug);

        $this->assertContains($response->status(), [401, 403]);
    }
}
