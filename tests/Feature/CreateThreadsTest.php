<?php
namespace Tests\Feature;

use App\Activity;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateThreadsTest extends TestCase
{
    /** @test  */
    function unauthorized_users_may_not_delete_threads()
    {
        $this->withExceptionHandling();

        $this->signIn();

        $thread = create('App\Thread');

        Auth::logout();

        $result = $this->delete($thread->path());

        $result->assertSee('login');
    }

    /** @test */
    function an_authenticated_user_can_create_new_forum_threads()
    {
        $this->withExceptionHandling();

        $this->signIn();

        $thread = make('App\Thread');

        $response = $this->followingRedirects()->post('/threads', $thread->toArray());

        $response->assertStatus(200);
        $response->assertSee($thread->name)->assertSee($thread->body);

    }
}
