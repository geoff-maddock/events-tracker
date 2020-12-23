<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Models\Thread;
use App\Models\ThreadCategory;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ThreadsTest extends TestCase
{
    private $thread;

    public function setUp():void
    {
        parent::setUp();

        $this->thread = Thread::factory()->create();
    }

    /** @test */
    public function a_thread_has_a_path()
    {
        $thread = Thread::factory()->create();
        $this->assertEquals(
            "/threads/{$thread->id}",
            $thread->path()
        );
    }

    /**
     * Test that threads are visible
     *
     * @test void
     */
    public function threads_browsable()
    {
        $response = $this->get('/threads')
        ->assertSee('Thread');
    }

    /** @test */
    public function a_user_can_read_a_single_thread()
    {
        $this->signIn();

        $user = User::find(1);
        $temp = $this->thread->id;

        // when we visit a thread page
        $this->actingAs($user)
            ->get('/threads/' . $temp)
            ->assertSee('Thread');
    }

    /** @test */
    public function a_user_can_read_posts_that_are_associated_with_a_thread()
    {
        //$this->signIn();

        //$user = factory('App\Models\User')->create();
        $user = User::find(1);
        // add that thread includes replies
        $post = Post::factory()
            ->create(['thread_id' => $this->thread->id]);

        $response = $this->actingAs($user)
            ->withSession(['foo' => 'bar'])
            ->get('/threads/' . $this->thread->id);

        // when we visit a thread page, see that post body
        $response->assertSee($post->body);
    }

    /** @test */
    public function a_thread_has_a_creator()
    {
        // add that thread
        $thread = Thread::factory()->make();

        $this->assertInstanceOf(User::class, $thread->creator);
    }

    /** @test */
    public function a_thread_can_add_a_reply()
    {
        // add a post to the thread
        $posts = $this->thread->addPost([
            'body' => 'Foobar',
            'name' => 'Generic Reply',
            'created_by' => 1
        ]);

        $this->assertInstanceOf(Post::class, $this->thread->posts->first());
    }

    /** @test */
    public function an_authenticated_user_can_create_new_forum_threads()
    {
        $this->signIn();

        $thread = Thread::factory()->make();

        $response = $this->post('/threads', $thread->toArray());

        $this->get($response->headers->get('Location'))
            ->assertSee($thread->name);
    }

    public function publishThread($overrides = [])
    {
        $this->withExceptionHandling()->signIn();

        $thread = Thread::factory()->make($overrides);

        $this->post('/threads', $thread->toArray());

        return $thread;
    }

    /** @test */
    public function a_user_can_filter_threads_according_to_a_tag()
    {
        $this->signIn();

        $category = ThreadCategory::factory()->create();
        $threadInCategory = Thread::factory()->create(['thread_category_id' => $category->id]);
        $threadNotInCategory = Thread::factory()->create(['thread_category_id' => null]);

        $this->get('/threads/category/' . $category->name)
            ->assertSee($threadInCategory->name)
            ->assertDontSee($threadNotInCategory->name);
    }

    /** @test */
    public function a_user_can_filter_threads_by_any_username()
    {
        $this->signIn(User::factory()->create(['name' => 'JohnDoe']));

        $threadByJohn = Thread::factory()->create(['created_by' => auth()->id()]);
        $threadNotByJohn = Thread::factory()->create(['created_by' => 1]);
        $threadNotByJohn->created_by = 1;
        $threadNotByJohn->save();

        $this->get('threads/filter?filter_user=JohnDoe')
            ->assertSee($threadByJohn->name)
            ->assertDontSee($threadNotByJohn->name);
    }
}
