<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PostsTest extends TestCase
{

	public function setUp()
	{
		parent::setUp();

		$this->post = factory('App\Post')->create();
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
    function a_post_has_a_body()
    {
        $this->withExceptionHandling()->signIn();

        $thread =  factory('App\Thread')->create();
        $post = make('App\Post', ['body' => null]);

        $this->post($thread->path() . '/posts', $post->toArray())
            ->assertSessionHasErrors('body');
    }

}
