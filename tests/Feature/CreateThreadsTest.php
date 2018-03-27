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
    //use DatabaseMigrations;
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->assertTrue(true);
    }

    /** @test  */
    function unauthorized_users_may_not_delete_threads()
    {
        $this->withExceptionHandling();

        $this->signIn();

        $thread = create('App\Thread');

        Auth::logout();

        $result = $this->delete($thread->path());

        //dump($result);
        $result->assertSee('login');


    }

    /** @test */
    function an_authenticated_user_can_create_new_forum_threads()
    {
        $this->withExceptionHandling();

        $this->signIn();

        $thread = make('App\Thread');

        $response = $this->post('/threads', $thread->toArray());

        $this->get($response->headers->get('Location'))
            ->assertSee($thread->name)
            ->assertSee($thread->body);
    }
}
