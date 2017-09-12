<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ParticipateInForum extends TestCase
{
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
    function an_authenticated_user_may_participate_in_forum_threads()
    {
        // given we have an authenticated user
        $this->be($user = factory('App\User')->create());

        // an an existing thread
        $thread = factory('App\Thread')->create();

        // when the user adds a post to the thread
        $post = factory('App\Post')->make();
        $this->post('/threads/'.$thread->id./'posts', $post->toArray());

        // then their reply should be visible on the page
        $this->get($thread->path())
            ->assertSee($post->body);
    }
}
