<?php

namespace Tests\Feature\Web;

use App\Models\Thread;
use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadsControllerSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['user_status_id' => UserStatus::ACTIVE]));
    }

    public function test_threads_index_renders(): void
    {
        Thread::factory()->count(2)->create();

        $this->get('/threads')->assertOk();
    }

    public function test_threads_show_renders(): void
    {
        $thread = Thread::factory()->create();

        $this->get('/threads/'.$thread->id)->assertOk();
    }

    public function test_threads_create_renders(): void
    {
        $this->get('/threads/create')->assertOk();
    }
}
