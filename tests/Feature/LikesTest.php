<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Thread;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LikesTest extends TestCase
{
    // refresh database and run migrations before test
    use RefreshDatabase;

    // reseed the database
    protected $seed = true;

    /** @test */
    public function a_guest_cannot_like_anything()
    {
        $this->withExceptionHandling()
            ->post('/posts/1/like')
            ->assertRedirect('/');
    }

    /** @test */
    public function an_authenticated_user_can_like_a_post()
    {
        $this->signIn();

        $post = Post::factory()->create();
        $likes = $post->likes;

        $this->post('/posts/' . $post->id . '/like');

        $post->refresh();

        $this->assertEquals($likes + 1, $post->likes);
    }

    /** @test */
    public function an_authenticated_user_can_like_a_thread()
    {
        $this->signIn();

        $thread = Thread::factory()->create();
        $likes = $thread->likes;

        $this->post('/threads/' . $thread->id . '/like');

        $thread->refresh();

        $this->assertEquals($likes + 1, $thread->likes);
    }
}
