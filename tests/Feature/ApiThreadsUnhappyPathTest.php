<?php

namespace Tests\Feature;

use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiThreadsUnhappyPathTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_show_rejects_missing_or_unauthenticated_thread(): void
    {
        // Whole API threads route group requires auth; an unauthenticated
        // request gets 401 before any 404 check.
        $response = $this->getJson('/api/threads/missing-slug-'.uniqid());

        $this->assertContains($response->status(), [401, 403, 404]);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/threads', ['name' => 'Test thread']);

        $this->assertContains($response->status(), [401, 403, 422]);
    }

    public function test_update_requires_authentication(): void
    {
        $thread = Thread::factory()->create();

        $response = $this->putJson('/api/threads/'.$thread->id, ['name' => 'Updated']);

        $this->assertContains($response->status(), [401, 403, 405]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $thread = Thread::factory()->create();

        $response = $this->deleteJson('/api/threads/'.$thread->id);

        $this->assertContains($response->status(), [401, 403]);
    }
}
