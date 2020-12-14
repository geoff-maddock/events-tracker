<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Thread;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateThreadsTest extends TestCase
{
    // uncomment this to refresh database and run migrations before test
    // use RefreshDatabase;

    /** @test  */
    public function unauthorized_users_may_not_delete_threads()
    {
        $this->withExceptionHandling();

        $this->signIn();

        $thread = create(Thread::class);

        Auth::logout();

        $result = $this->delete($thread->path());

        $result->assertSee('login');
    }

    /** @test */
    public function an_authenticated_user_can_create_new_forum_threads()
    {
        $this->withExceptionHandling();

        $this->signIn();

        $thread = make(Thread::class);

        $response = $this->followingRedirects()->post('/threads', $thread->toArray());

        $response->assertStatus(200);
        $response->assertSee($thread->name)->assertSee($thread->body);
    }
}
