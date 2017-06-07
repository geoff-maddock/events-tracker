<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ThreadsTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->thread = factory('App\Thread')->create();
	}

    /**
	 * Test that threads are visible
     *
     * @test void
     */
    public function threads_browsable()
    {
        $response = $this->get('/threads');
    }


    /** @test */
    function a_user_can_read_a_single_thread()
    {
    	// when we visit a thread page
    	//$this->get('/threads/' . $this->thread->id)
    	//	->see($this->thread->name);
    }


    /** @test */
    function a_user_can_read_posts_that_are_associated_with_a_thread()
    {
    	// add that thread includes replies
    	$reply = factory('App\Post')
    		->create(['thread_id' => $this->thread->id]);

    	// when we visit a thread page
    	$this->get('/threads/' . $this->thread->id)
    		->see($reply->body);
    }

     /** @test */
    function a_thread_has_a_creator()
    {
        // add that thread 
        $thread = factory('App\Thread')->create();
 
        $this->assertInstanceOf('App\User', $thread->creator);
    }

     /** @test */
    function a_thread_can_add_a_reply()
    {
        // add that thread 
        $this->thread->addReply([
            'body' => 'Foobar',
            'user_id' => 1
            ]);
    }


    /** @test */
    function an_authenticated_user_may_participate_in_threads()
    {
        // given we have an authenticated user
        $user = factory('App\User')->create();

        $this->be($user);

        $thread = factory('App\Thread')->create();

        $post = factory('App\Post')->make();

        $this->post('/threads/'.$thread->id.'/post', $post->toArray());

        // then their reply should be visible
        $this->get($this->thread->path());
    }

    /** @test */
    function an_authenticated_user_can_create_new_forum_threads()
    {
        $this->signIn();

        $thread = make('App\Thread');

        $response = $this->post('/threads', $thread->toArray());

        $this->get($response->headers->get('Location'))
            ->assertSee($thread->name)
            ->assertSee($thread->body);

    }

    /** @test */
    function a_thread_requires_a_name()
    {
        $this->publishThread('name' => null])
            ->assertSessionHasErrors('name');

    }

    /** @test */
    function a_thread_requires_a_body()
    {
        $this->publishThread('body' => null])
            ->assertSessionHasErrors('body');

    }

    /** @test */
    function a_thread_requires_a_valid_forum()
    {
        factory('App\Forum', 2)->create();

        $this->publishThread('forum_id' => null])
            ->assertSessionHasErrors('forum_id');

        $this->publishThread('forum_id' => 99999999])
            ->assertSessionHasErrors('forum_id');

    }

    public function publishThread($overrides = [])
    {
        $this->withExceptionHandling()->signIn()

        $thread = make('App\Thread', $overrides);

        $this->post('/threads', $thread->toArray())
    }

}
