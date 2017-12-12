<?php
namespace Tests;
use App\User;
use App\Thread;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ThreadsTest extends TestCase
{
    private $thread;

	public function setUp()
	{
		parent::setUp();

		$this->thread = factory('App\Thread')->create();
	}

    /** @test */
    function a_thread_has_a_path()
    {
        $thread = create('App\Thread');
        $this->assertEquals(
            "/threads/{$thread->id}", $thread->path()
        );
    }

    /**
	 * Test that threads are visible
     *
     * @test void
     */
    function threads_browsable()
    {
        $response = $this->get('/threads')
        ->assertSee('Thread');
    }


    /** @test */
    function a_user_can_read_a_single_thread()
    {
        $this->signIn();

        $user = \App\User::find(1);
        $temp = $this->thread->id;

    	// when we visit a thread page
    	$this->actingAs($user)
            ->get('/threads/' . $temp)
    		->assertSee('Thread');
    }


    /** @test */
    function a_user_can_read_posts_that_are_associated_with_a_thread()
    {
        //$this->signIn();

        //$user = factory('App\User')->create();
        $user = \App\User::find(1);
    	// add that thread includes replies
    	$post = factory('App\Post')
    		->create(['thread_id' => $this->thread->id]);

        $response = $this->actingAs($user)
            ->withSession(['foo' => 'bar'])
            ->get('/threads/' . $this->thread->id);

    	// when we visit a thread page, see that post body
        $response->assertSee($post->body);
    }

     /** @test */
    function a_thread_has_a_creator()
    {
        // add that thread 
        $thread = factory('App\Thread')->make();
 
        $this->assertInstanceOf('App\User', $thread->creator);
    }

     /** @test */
    function a_thread_can_add_a_reply()
    {
        // add a post to the thread
        $posts = $this->thread->addPost([
            'body' => 'Foobar',
            'name' => 'Generic Reply',
            'created_by' => 1
            ]);

        $this->assertInstanceOf('App\Post', $this->thread->posts->first());
    }


    /** @test */
    function an_authenticated_user_can_create_new_forum_threads()
    {
        $this->signIn();

        $thread = factory('App\Thread')->make();

        $response = $this->post('/threads', $thread->toArray());

        $this->get($response->headers->get('Location'))
            ->assertSee($thread->name);

    }



    public function publishThread($overrides = [])
    {
        $this->withExceptionHandling()->signIn();

        $thread = factory('App\Thread', $overrides)->make();

        $this->post('/threads', $thread->toArray());

        return $thread;
    }

    /** @test */
    function a_user_can_filter_threads_according_to_a_tag()
    {
        $category = create('App\ThreadCategory');
        $threadInCategory = create('App\Thread', ['thread_category_id' => $category->id]);
        $threadNotInCategory = create('App\Thread');

        $this->get('/threads/category/' . $category->name)
            ->assertSee($threadInCategory->name)
            ->assertDontSee($threadNotInCategory->name);
    }

    /** @test */
    function a_user_can_filter_threads_by_any_username()
    {
        $this->signIn(create('App\User', ['name' => 'JohnDoe']));

        $threadByJohn = create('App\Thread', ['created_by' => auth()->id()]);
        $threadNotByJohn = create('App\Thread', ['created_by' => 1]);
        $threadNotByJohn->created_by = 1;
        $threadNotByJohn->save();

        $this->get('threads/filter?filter_user=JohnDoe')
            ->assertSee($threadByJohn->name)
            ->assertDontSee($threadNotByJohn->name);
    }


}
