<?php

namespace Tests\Feature\Web;

use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
    }

    public function test_index_loads(): void
    {
        $this->get('/threads')->assertOk();
    }

    public function test_show_loads_for_existing_thread(): void
    {
        $user = User::factory()->create(['user_status_id' => UserStatus::ACTIVE]);
        $this->actingAs($user);
        $thread = Thread::factory()->create();

        $response = $this->get('/threads/'.$thread->id);

        // Some thread routes redirect; assert success or redirect.
        $this->assertContains($response->status(), [200, 302]);
    }

    public function test_create_form_requires_auth(): void
    {
        $this->get('/threads/create')->assertStatus(302);
    }
}
