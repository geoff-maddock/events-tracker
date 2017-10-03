<?php
namespace Tests;

use App\Post;
use Carbon\Carbon;
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

    /** @test */
    function it_has_an_owner()
    {
        $post = factory('App\Post')->create();

        $this->assertInstanceOf('App\User', $post->user);
    }

    /** @test */
    function it_knows_if_it_was_just_published()
    {
        $post = create('App\Post');
        $this->assertTrue($post->wasJustPublished());
        $post->created_at = Carbon::now()->subMonth();
        $this->assertFalse($post->wasJustPublished());
    }


    /** @test */
    function it_can_detect_all_mentioned_users_in_the_body()
    {
        $post = new Post([
            'body' => '@JaneDoe wants to talk to @JohnDoe'
        ]);
        $this->assertEquals(['JaneDoe', 'JohnDoe'], $post->mentionedUsers());
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

//    /** @test */
//    function a_post_has_a_body()
//    {
//        $this->withExceptionHandling()->signIn();
//
//        $thread =  factory('App\Thread')->create();
//        $post = factory('App\Post', ['body' => null])->make();
//
//        $this->post($thread->path() . '/posts', $post->toArray())
//            ->assertSessionHasErrors('body');
//    }



}
