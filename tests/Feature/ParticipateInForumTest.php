<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ParticipateInForumTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

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
    public function an_authenticated_user_may_participate_in_forum_threads()
    {
        // given we have an authenticated user
        $this->be($user = User::factory()->create());

        // an an existing thread
        $thread = Thread::factory()->create();

        // when the user adds a post to the thread
        $post = Post::factory()->make();
        $this->post('/threads/' . $thread->id . '/posts', $post->toArray());

        // then their reply should be visible on the page
        $this->get($thread->path())
            ->assertSee($post->body);
    }
}
